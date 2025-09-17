from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from datetime import date, datetime
import os
from dotenv import load_dotenv

# Load environment variables from .env
load_dotenv()
# Flask app setup
app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})
# Database config from environment variables
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "localhost"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "csaraebackuponline"),
    "port": int(os.getenv("DB_PORT", 3306)),
}


# ---------------------- Date Utilities ----------------------
def to_iso(d):
    """Convert MySQL date/time to ISO string."""
    if d is None or d == "" or d in ["0000-00-00", "0000-00-00 00:00:00"]:
        return None

    if isinstance(d, datetime):
        return d.isoformat()
    if isinstance(d, date):
        return datetime(d.year, d.month, d.day).isoformat()

    if isinstance(d, str):
        for fmt in ("%Y-%m-%d", "%Y-%m-%d %H:%M:%S", "%a, %d %b %Y %H:%M:%S %Z"):
            try:
                return datetime.strptime(d, fmt).isoformat()
            except ValueError:
                continue
        return None

    return None


def clean_date(d):
    """Return only YYYY-MM-DD from a datetime/date."""
    if not d:
        return None
    iso = to_iso(d)
    if iso:
        return iso.split("T")[0]
    return None


def validate_date_format(date_string):
    """Validate if date string is in YYYY-MM-DD format"""
    if not date_string:
        return False
    try:
        datetime.strptime(date_string, "%Y-%m-%d")
        return True
    except ValueError:
        return False


# ---------------------- Data Fetcher ----------------------
def fetch_data(limit=50, offset=0, search=None, start_date=None, end_date=None):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    # Build WHERE conditions dynamically
    conditions = []
    params = []

    if search:
        # Search across multiple fields in projects table
        search_conditions = [
            "CAST(IFNULL(p.project_id,'') AS CHAR) LIKE %s",
            "p.project_name LIKE %s",
            "p.urgency LIKE %s",
            "p.state LIKE %s",
            "p.project_manager LIKE %s",
            "p.project_details LIKE %s",
            "p.p_team LIKE %s",
            "p.assign_to LIKE %s",
            "p.reopen_status LIKE %s",
            "s.subproject_name LIKE %s",
            "s.urgency LIKE %s",
            "s.subproject_status LIKE %s",
            "s.subproject_details LIKE %s",
            "s.p_team LIKE %s",
            "s.assign_to LIKE %s",
            "s.reopen_status LIKE %s",
            "c.customer_name LIKE %s",
            "c.contact_name LIKE %s",
            "c.contact_email LIKE %s",
            "c.contact_phone_number LIKE %s",
            "c.address LIKE %s",
            "CAST(IFNULL(i.invoice_number,'') AS CHAR) LIKE %s",
            "i.payment_status LIKE %s",
            "i.comments LIKE %s",
            "CAST(IFNULL(r.invoice_number,'') AS CHAR) LIKE %s",
            "r.project_status LIKE %s",
            "r.comments LIKE %s",
            "CAST(IFNULL(u.invoice_no,'') AS CHAR) LIKE %s",
            "u.comments LIKE %s",
        ]

        # Add OR conditions for search across all fields
        conditions.append(f"({' OR '.join(search_conditions)})")
        # Add the search term parameter for each field (repeat for all search conditions)
        params.extend([f"%{search}%"] * len(search_conditions))

    # FIXED: Simplified date filtering logic
    if start_date and validate_date_format(start_date):
        conditions.append("p.start_date >= %s")
        params.append(start_date)
        print(f"Applying start date filter: {start_date}")

    if end_date and validate_date_format(end_date):
        conditions.append("p.end_date <= %s")
        params.append(end_date)
        print(f"Applying end date filter: {end_date}")

    where_clause = f"WHERE {' AND '.join(conditions)}" if conditions else ""

    # Debug: Print the final query and parameters
    print(f"Final WHERE clause: {where_clause}")
    print(f"Query parameters: {params}")

    # ----------------- Count total projects -----------------
    count_query = f"""
    SELECT COUNT(DISTINCT p.project_id) AS total
    FROM projects p
    LEFT JOIN subprojects s ON s.project_id = p.project_id
    LEFT JOIN contacts c ON c.contact_id = p.contact_id
    LEFT JOIN csa_finance_invoiced i ON i.project_id = p.project_id
    LEFT JOIN csa_finance_readytobeinvoiced r ON r.project_id = p.project_id
    LEFT JOIN unpaidinvoices u ON u.project_id = p.project_id
    {where_clause}
    """

    cursor.execute(count_query, tuple(params))
    total_count = cursor.fetchone()["total"]

    # ----------------- Fetch paginated projects -----------------
    query = f"""
    SELECT DISTINCT 
        p.project_id, p.project_name, p.urgency, p.start_date, p.end_date,
        p.state, p.project_manager, p.project_details, p.p_team,
        p.assign_to, p.reopen_status, p.contact_id,
        s.subproject_name, s.subproject_status, s.urgency AS sub_urgency
    FROM projects p
    LEFT JOIN subprojects s ON s.project_id = p.project_id
    LEFT JOIN contacts c ON c.contact_id = p.contact_id
    LEFT JOIN csa_finance_invoiced i ON i.project_id = p.project_id
    LEFT JOIN csa_finance_readytobeinvoiced r ON r.project_id = p.project_id
    LEFT JOIN unpaidinvoices u ON u.project_id = p.project_id
    {where_clause}
    ORDER BY p.start_date ASC
    LIMIT %s OFFSET %s
    """

    cursor.execute(query, (*params, limit, offset))
    projects = cursor.fetchall()

    # Collect project_ids + contact_ids
    project_ids = [p["project_id"] for p in projects]
    contact_ids = [p["contact_id"] for p in projects if p.get("contact_id")]

    if not project_ids:
        cursor.close()
        conn.close()
        return [], total_count

    # ----------------- Fetch related subprojects -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, subproject_name, urgency, start_date, sub_end_date, 
            subproject_status, subproject_details, assign_to, p_team, reopen_status
        FROM subprojects
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    subprojects = cursor.fetchall()

    # ----------------- Fetch contacts -----------------
    contacts = []
    if contact_ids:
        cursor.execute(
            f"""
            SELECT 
                contact_id, customer_id, customer_name, contact_name, 
                contact_email, contact_phone_number, address, registration_date
            FROM contacts
            WHERE contact_id IN ({",".join(["%s"]*len(contact_ids))})
            """,
            tuple(contact_ids),
        )
        contacts = cursor.fetchall()

        # ----------------- Fetch uninvoiced -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, service_date, due_date, 
            project_status, price, comments
        FROM csa_finance_uninvoiced
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    uninvoiced = cursor.fetchall()

    # ----------------- Fetch invoices -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, invoice_number, service_date, due_date, 
            payment_status, amount, comments
        FROM csa_finance_invoiced
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    invoices = cursor.fetchall()

    # ----------------- Fetch ready to be invoiced -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, invoice_number, service_date, due_date, 
            project_status, price, comments
        FROM csa_finance_readytobeinvoiced
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    ready_invoices = cursor.fetchall()

    # ----------------- Fetch unpaid invoices -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, invoice_no, comments, invoice_date, booked_date, 
            received_date, amount
        FROM unpaidinvoices
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    unpaid_invoices = cursor.fetchall()

    # ----------------- Fetch paid invoices -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, invoice_no, booked_date, received_date, 
            amount, comments
        FROM paidinvoices
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    paid_invoices = cursor.fetchall()

    # ----------------- Fetch ready to pay -----------------
    cursor.execute(
        f"""
        SELECT 
            project_id, invoice_no, booked_date, received_date, 
            amount, comments
        FROM ready_to_pay
        WHERE project_id IN ({",".join(["%s"]*len(project_ids))})
        """,
        tuple(project_ids),
    )
    ready_to_pay = cursor.fetchall()

    cursor.close()
    conn.close()

    # ---------------------- Build project structure ----------------------
    project_map = {}
    for p in projects:
        pid = int(p["project_id"])
        project_map[pid] = {
            "id": pid,
            "name": p["project_name"],
            "start": to_iso(p["start_date"]),
            "end": to_iso(p["end_date"]),
            "urgency": (p["urgency"] or "").strip().lower(),
            "state": p.get("state"),
            "project_manager": p.get("project_manager"),
            "project_details": p.get("project_details"),
            "p_team": p.get("p_team"),
            "assign_to": p.get("assign_to"),
            "reopen_status": p.get("reopen_status"),
            "contact_id": p.get("contact_id"),
            "children": [],
            "invoices": [],
            "ready_to_invoice": [],
            "unpaid_invoices": [],
            "uninvoiced": [],
            "contacts": [],
            "paid_invoices": [],
            "ready_to_pay": [],
        }

    # Attach subprojects
    for sp in subprojects:
        pid = int(sp["project_id"])
        if pid in project_map:
            sp_start = to_iso(sp["start_date"])
            sp_end = to_iso(sp["sub_end_date"])
            if sp_start and sp_end:
                project_map[pid]["children"].append(
                    {
                        "id": f"SP{pid}_{sp['subproject_status']}",
                        "name": sp["subproject_name"],
                        "start": sp_start,
                        "end": sp_end,
                        "urgency": (sp["urgency"] or "").strip().lower(),
                        "status": sp.get("subproject_status"),
                        "subproject_details": sp.get("subproject_details"),
                        "p_team": sp.get("p_team"),
                        "assign_to": sp.get("assign_to"),
                        "reopen_status": sp.get("reopen_status"),
                    }
                )

    # Attach invoices
    for inv in invoices:
        pid = int(inv["project_id"])
        if pid in project_map:
            payment_status = inv.get("payment_status") or "not paid"
            project_map[pid]["invoices"].append(
                {
                    "invoice_number": inv.get("invoice_number"),
                    "service_date": clean_date(inv.get("service_date")),
                    "due_date": clean_date(inv.get("due_date")),
                    "payment_status": payment_status,
                    "amount": inv.get("amount"),
                    "comments": inv.get("comments"),
                }
            )

    # Attach ready to be invoiced
    for r in ready_invoices:
        pid = int(r["project_id"])
        if pid in project_map:
            status = r.get("project_status") or "ready to be invoiced"
            if str(status).lower() == "invoiced":
                continue
            project_map[pid]["ready_to_invoice"].append(
                {
                    "invoice_number": r.get("invoice_number"),
                    "service_date": clean_date(r.get("service_date")),
                    "due_date": clean_date(r.get("due_date")),
                    "project_status": status,
                    "price": r.get("price"),
                    "comments": r.get("comments"),
                }
            )

    # Attach unpaid invoices
    for u in unpaid_invoices:
        pid = int(u["project_id"])
        if pid in project_map:
            project_map[pid]["unpaid_invoices"].append(
                {
                    "invoice_no": u.get("invoice_no"),
                    "comments": u.get("comments"),
                    "invoice_date": clean_date(u.get("invoice_date")),
                    "booked_date": clean_date(u.get("booked_date")),
                    "received_date": clean_date(u.get("received_date")),
                    "amount": u.get("amount"),
                }
            )

        # Attach paid invoices
    for pi in paid_invoices:
        pid = int(pi["project_id"])
        if pid in project_map:
            project_map[pid]["paid_invoices"].append(
                {
                    "invoice_no": pi.get("invoice_no"),
                    "booked_date": clean_date(pi.get("booked_date")),
                    "received_date": clean_date(pi.get("received_date")),
                    "amount": pi.get("amount"),
                    "comments": pi.get("comments"),
                }
            )

    # Attach ready to pay
    for rp in ready_to_pay:
        pid = int(rp["project_id"])
        if pid in project_map:
            project_map[pid]["ready_to_pay"].append(
                {
                    "invoice_no": rp.get("invoice_no"),
                    "booked_date": clean_date(rp.get("booked_date")),
                    "received_date": clean_date(rp.get("received_date")),
                    "amount": rp.get("amount"),
                    "comments": rp.get("comments"),
                }
            )

        # Attach uninvoiced
    for ui in uninvoiced:
        pid = int(ui["project_id"])

        # Normalize project_status for safe comparison
        status = (ui.get("project_status") or "").strip()

        # Skip if status is "MovedToReadyToBeInvoicedTable" (case-insensitive)
        if status.lower() == "movedtoreadytobeinvoicedtable":
            continue

        if pid in project_map:
            project_map[pid]["uninvoiced"].append(
                {
                    "service_date": clean_date(ui.get("service_date")),
                    "due_date": clean_date(ui.get("due_date")),
                    "project_status": status or "Uninvoiced",  # default if null/empty
                    "price": ui.get("price"),
                    "comments": ui.get("comments"),
                }
            )

    # Attach contacts
    for c in contacts:
        for p in projects:
            if p["contact_id"] == c["contact_id"]:
                pid = int(p["project_id"])
                if pid in project_map:
                    project_map[pid]["contacts"].append(
                        {
                            "contact_id": c.get("contact_id"),
                            "customer_id": c.get("customer_id"),
                            "customer_name": c.get("customer_name"),
                            "contact_name": c.get("contact_name"),
                            "contact_email": c.get("contact_email"),
                            "contact_phone_number": c.get("contact_phone_number"),
                            "address": c.get("address"),
                            "registration_date": clean_date(c.get("registration_date")),
                        }
                    )

    # Sort children and projects
    for pid, project in project_map.items():
        project["children"].sort(
            key=lambda x: (
                int(x["status"]) if x["status"] and str(x["status"]).isdigit() else 0
            )
        )

    projects_list = list(project_map.values())
    projects_list.sort(
        key=lambda p: (
            datetime.fromisoformat(p["start"]) if p["start"] else datetime.max
        )
    )

    return projects_list, total_count


# ---------------------- Routes ----------------------
@app.route("/health")
def health():
    return {"ok": True}


@app.route("/gantt-data")
def gantt_data():
    try:
        limit = int(request.args.get("limit", 50))
        page = int(request.args.get("page", 1))
        offset = (page - 1) * limit
        search = request.args.get("search", "").strip()
        start_date = request.args.get("start_date")
        end_date = request.args.get("end_date")

        # Debug: Print the received parameters
        print(
            f"Received parameters: search='{search}', start_date='{start_date}', end_date='{end_date}'"
        )

        data, total = fetch_data(
            limit=limit,
            offset=offset,
            search=search,
            start_date=start_date,
            end_date=end_date,
        )

        return jsonify(
            {
                "page": page,
                "limit": limit,
                "count": len(data),
                "total": total,
                "projects": data,
            }
        )
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=5000)
