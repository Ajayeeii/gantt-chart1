<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Project Gantt Chart</title>
    <link rel="stylesheet" href="libs/dhtmlxgantt.css">
    <script src="libs/dhtmlxgantt.js"></script>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        #gantt_here {
            width: 100%;
            height: 100vh;
        }

        /* Modal styling */
        #projectModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        #projectModal .content {
            background: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 8px;
        }

        #projectModal .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Add vertical borders between columns */
        .gantt_grid_data .gantt_cell,
        .gantt_grid_scale .gantt_grid_head_cell {
            border-right: 1px solid #ccc;
        }

        /* Add horizontal row borders */
        .gantt_grid_data .gantt_row {
            border-bottom: 1px solid #e0e0e0;
        }

        /* Add header background */
        .gantt_grid_scale .gantt_grid_head_cell {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        /* Optional: align text nicely */
        .gantt_grid_data .gantt_cell {
            padding: 6px;
        }
    </style>
</head>

<body>
    <h2 style="padding:10px;">Project Gantt Chart</h2>

    <div style="padding:10px;">
        <label for="scaleSelect"><strong>View:</strong></label>
        <select id="scaleSelect" onchange="setScale(this.value)">
            <option value="week">Week</option>
            <option value="month" selected>Month</option>
            <option value="year">Year</option>
        </select>
    </div>
    <label for="pageSize"><strong>Show:</strong></label>
    <select id="pageSize" onchange="changePageSize(this.value)">
        <option value="25">25</option>
        <option value="50" selected>50</option>
        <option value="100">100</option>
    </select>
    <!-- search filter -->
    <button id="prevBtn" onclick="prevPage()">Previous</button>
    <span id="pageInfo"></span>
    <button id="nextBtn" onclick="nextPage()">Next</button>
    <label for="searchBox"><strong>Search:</strong></label>
    <input type="text" id="searchBox" placeholder="Search projects, customers, invoices..."
        style="width:250px; padding:5px; margin-left:10px;">
    <button onclick="applySearch()">Search</button>
    <!-- Date filter -->
    <label>Start Date: <input type="date" id="startDate"></label>
    <label>End Date: <input type="date" id="endDate"></label>
    <button onclick="applyDateFilter()">Apply Date Filter</button>
    <button onclick="resetFilters()">Reset</button>


    <div id="gantt_here"></div>

    <!-- Project Detail Modal -->
    <div id="projectModal">
        <div class="content">
            <span class="close" onclick="document.getElementById('projectModal').style.display='none'">&times;</span>
            <h2 id="modalTitle">Project Details</h2>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let pageSize = 50;
        let totalItems = 0;
        let searchQuery = "";
        let filterStartDate = "";
        let filterEndDate = "";

        function applySearch() {
            searchQuery = document.getElementById("searchBox").value.trim();
            currentPage = 1; // reset to first page for new search
            loadData();
        }

        function applyDateFilter() {
            filterStartDate = document.getElementById("startDate").value;
            filterEndDate = document.getElementById("endDate").value;
            currentPage = 1;
            loadData();
        }

        function resetFilters() {
            searchQuery = "";
            filterStartDate = "";
            filterEndDate = "";
            document.getElementById("searchBox").value = "";
            document.getElementById("startDate").value = "";
            document.getElementById("endDate").value = "";
            currentPage = 1;
            loadData();
        }


        function setScale(mode) {
            switch (mode) {
                case "week":
                    gantt.config.scales = [
                        { unit: "week", step: 1, format: "Week %W" },
                        { unit: "day", step: 1, format: "%D %d" }
                    ];
                    break;

                case "month":
                    gantt.config.scales = [
                        { unit: "month", step: 1, format: "%F %Y" },
                        { unit: "week", step: 1, format: "Week %W" }
                    ];
                    break;

                case "year":
                    gantt.config.scales = [
                        { unit: "year", step: 1, format: "%Y" },
                        { unit: "month", step: 1, format: "%M" }
                    ];
                    break;
            }
            gantt.render();
        }

        // Urgency → color mapping
        const urgencyColors = {
            red: "#ff0000",    // Very Urgent
            navy: "#1A1A55",   // Completed
            green: "#188918",  // In Progress
            orange: "#FFA500", // Urgent
            yellow: "#FFFF00", // On Hold
            purple: "#8B008B", // Closed
            white: "#fefdfd",  // Waiting
            gray: "#bfbfbf"    // Not started
        };

        // Configure columns
        gantt.config.columns = [
            { name: "text", label: "Name", tree: true, width: 200 },
            {
                name: "projectId",
                label: "Project ID",
                align: "center",
                width: 120,
                template: function (task) {
                    let badge = "";
                    if (task.reopen_status) {
                        const color = task.reopen_status === "Yes" ? "#ff4d4f" : "#52c41a";
                        badge = `<span style="display:inline-block; padding:2px 6px; border-radius:4px; background-color:${color}; color:#fff; font-size:12px; margin-left:5px;">
                    ${task.reopen_status}
                </span>`;
                    }
                    return task.projectId + badge;
                }
            },
            { name: "project_manager", label: "Project Manager", align: "center", width: 150 },
            { name: "customer_name", label: "Customer", align: "center", width: 150 }
        ];


        gantt.config.date_format = "%Y-%m-%d";

        // Color bars based on urgency
        gantt.templates.task_class = function (start, end, task) {
            if (task.urgency && urgencyColors[task.urgency]) {
                return "task_" + task.urgency;
            }
            return "";
        };

        // Custom task bar style
        Object.entries(urgencyColors).forEach(([urgency, color]) => {
            const style = document.createElement("style");
            style.textContent = `.gantt_task_line.task_${urgency} { background-color: ${color} !important; border-color: ${color} !important; }`;
            document.head.appendChild(style);
        });

        gantt.init("gantt_here");
        setScale("month");

        function changePageSize(size) {
            pageSize = parseInt(size);
            currentPage = 1;
            loadData();
        }

        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadData();
            }
        }

        function nextPage() {
            if (currentPage * pageSize < totalItems) {
                currentPage++;
                loadData();
            }
        }

        // Fetch backend data
        function loadData() {
            fetch(`http://localhost:5000/gantt-data?limit=${pageSize}&page=${currentPage}&search=${encodeURIComponent(searchQuery)}&start_date=${encodeURIComponent(filterStartDate)}&end_date=${encodeURIComponent(filterEndDate)}`)
                .then(res => res.json())
                .then(data => {
                    totalItems = data.total;

                    // Update pagination info
                    const totalPages = totalItems > 0 ? Math.ceil(totalItems / pageSize) : 1;
                    document.getElementById("pageInfo").innerText =
                        `Page ${data.page || 1} of ${totalPages}`;
                    document.getElementById("prevBtn").disabled = currentPage === 1;
                    document.getElementById("nextBtn").disabled = currentPage * pageSize >= totalItems;

                    // Reset tasks each load
                    const tasks = { data: [], links: [] };

                    // 🔥 Your full project loop stays here
                    if (data.projects && Array.isArray(data.projects)) {
                        data.projects.forEach(project => {
                            const projectId = project.id;
                            const start = project.start?.split("T")[0];
                            const end = project.end?.split("T")[0];

                            // Project Info
                            // Badge for reopen_status
                            let reopenBadge = "";
                            if (project.reopen_status) {
                                const badgeColor = project.reopen_status === "Yes" ? "#ff4d4f" : "#ff4d4f";
                                reopenBadge = `<span style="display:inline-block; padding:2px 8px; font-size:12px; border-radius:4px; background-color:${badgeColor}; color:#fff; margin-left:5px;">
                        ${project.reopen_status}
                   </span>`;
                            }

                            // Project Info with badge
                            let projectInfoHTML = `
                            <p><strong>Name:</strong> ${project.name || "N/A"} ${reopenBadge}</p>
<div style="margin-bottom:15px; padding:10px; border:1px solid #ccc; border-radius:6px;">
    <p><strong>Name:</strong> ${project.name || "N/A"} ${reopenBadge}</p>
    <p><strong>Description:</strong> ${project.project_details || project.subproject_details || "N/A"}</p>
    <p><strong>Status:</strong> ${project.urgency || "N/A"}</p>
    <p><strong>Start Date:</strong> ${project.start ? new Date(project.start).toDateString() : "N/A"}</p>
    <p><strong>Expected End Date:</strong> ${project.end ? new Date(project.end).toDateString() : "N/A"}</p>
    <p><strong>Engineer:</strong> ${project.assign_to || "N/A"}</p>
    <p><strong>Project Manager:</strong> ${project.project_manager || "N/A"}</p>
    <p><strong>Team:</strong> ${project.p_team || "N/A"}</p>
    <p><strong>State:</strong> ${project.state || "N/A"}</p>
</div>
`;


                            // Receivable Details
                            let receivableHTML = `
                            <h3>Receivable Details</h3>
                            <table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; width:100%; margin-bottom:15px;">
                                <thead style="background:#f5f5f5;">
                                    <tr>
                                        <th>Invoice</th><th>Service Date</th><th>Due Date</th><th>Status</th><th>Amount</th><th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                            let receivables = [...(project.invoices ?? []), ...(project.ready_to_invoice ?? [])];
                            if (receivables.length > 0) {
                                receivables.forEach(inv => {
                                    receivableHTML += `
                                    <tr>
                                        <td>${inv.invoice_number || "N/A"}</td>
                                        <td>${inv.service_date || "N/A"}</td>
                                        <td>${inv.due_date ? new Date(inv.due_date).toLocaleDateString() : "N/A"}</td>
                                        <td>${inv.payment_status ? inv.payment_status : (inv.project_status || "Ready to be Invoiced")}</td>
                                        <td style="text-align:right;">${inv.amount ? "$" + inv.amount : inv.price ? "$" + inv.price : "N/A"}</td>
                                        <td>${inv.comments || "N/A"}</td>
                                    </tr>
                                `;
                                });
                            } else {
                                receivableHTML += `
                                <tr>
                                    <td colspan="6" style="text-align:center;">N/A</td>
                                </tr>
                            `;
                            }
                            receivableHTML += `</tbody></table>`;

                            // Payable Details
                            let payableHTML = `
                            <h3>Payable Details</h3>
                            <table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; width:100%; margin-bottom:15px;">
                                <thead style="background:#f5f5f5;">
                                    <tr>
                                        <th>Invoice No</th><th>Invoice Date</th><th>Booked Date</th><th>Received Date</th><th>Amount</th><th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                            if ((project.unpaid_invoices?.length || 0) > 0) {
                                project.unpaid_invoices.forEach(inv => {
                                    payableHTML += `
                                    <tr>
                                        <td>${inv.invoice_no || "N/A"}</td>
                                        <td>${inv.invoice_date || "N/A"}</td>
                                        <td>${inv.booked_date || "N/A"}</td>
                                        <td>${inv.received_date || "N/A"}</td>
                                        <td style="text-align:right;">${inv.amount ? "$" + inv.amount : "N/A"}</td>
                                        <td>${inv.comments || "N/A"}</td>
                                    </tr>
                                `;
                                });
                            } else {
                                payableHTML += `
                                <tr>
                                    <td colspan="6" style="text-align:center;">N/A</td>
                                </tr>
                            `;
                            }
                            payableHTML += `</tbody></table>`;

                            // Contact Details
                            let contactDetailsHTML = "<h3></h3>";
                            if (project.contacts && project.contacts.length > 0) {
                                project.contacts.forEach(c => {
                                    contactDetailsHTML += `
                                    <div style="margin-bottom:10px; padding:8px; border:1px solid #ddd; border-radius:6px;">
                                        Customer: ${c.customer_name || "N/A"}<br>
                                        Con. Name: ${c.contact_name || "N/A"}<br>
                                        Email: ${c.contact_email || "N/A"}<br>
                                        Phone: ${c.contact_phone_number || "N/A"}<br>
                                        Address: ${c.address || "N/A"}<br>
                                    </div>
                                `;
                                });
                            } else {
                                contactDetailsHTML += `
                                <table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; width:100%; margin-bottom:15px;">
                                    <thead style="background:#f5f5f5;">
                                        <tr><th>Customer</th><th>Contact Name</th><th>Email</th><th>Phone</th><th>Address</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="5" style="text-align:center;">N/A</td></tr>
                                    </tbody>
                                </table>
                            `;
                            }

                            // Merge everything
                            const fullDetailsHTML = `
                            ${projectInfoHTML}
                            ${receivableHTML}
                            ${payableHTML}
                            <h3>Contacts</h3>
                            ${contactDetailsHTML}
                        `;

                            // Parent task
                            tasks.data.push({
                                id: projectId,
                                text: project.name,
                                start_date: start,
                                end_date: end,
                                progress: 0,
                                open: true,
                                projectId: projectId,
                                urgency: project.urgency,
                                details: fullDetailsHTML,
                                project_manager: project.project_manager || "N/A",
                                customer_name: (project.contacts && project.contacts.length > 0)
                                    ? project.contacts[0].customer_name || "N/A"
                                    : "N/A",
                                reopen_status: project.reopen_status || null
                            });


                            // Children
                            project.children?.forEach((child, index) => {
                                const childStart = child.start?.split("T")[0];
                                const childEnd = child.end?.split("T")[0];
                                const childId = `${projectId}-${index}`;

                                // Badge for reopen_status
                                let childReopenBadge = "";
                                if (child.reopen_status) {
                                    const badgeColor = child.reopen_status === "Yes" ? "#ff4d4f" : "#ff4d4f";
                                    childReopenBadge = `<span style="display:inline-block; padding:2px 6px; font-size:10px; border-radius:12px; background-color:${badgeColor}; color:#fff; margin-left:5px;">
                                   ${child.reopen_status}
                              </span>`;
                                }

                                const combinedDetails = `
        <strong>Subproject:</strong> ${child.name || `Subproject ${index + 1}`} ${childReopenBadge}<br>
        Start: ${childStart || "N/A"}<br>
        End: ${childEnd || "N/A"}<br>
        Status: ${child.status || "N/A"}<br>
        Urgency: ${child.urgency || "N/A"}<br>
        Details: ${child.subproject_details || "N/A"}
        <hr>
        ${receivableHTML}
        ${payableHTML}
        ${contactDetailsHTML}
    `;

                                tasks.data.push({
                                    id: childId,
                                    text: child.name || `Subproject ${index + 1}`,
                                    start_date: childStart,
                                    end_date: childEnd,
                                    progress: 0,
                                    parent: projectId,
                                    open: true,
                                    projectId: projectId,
                                    urgency: child.urgency,
                                    details: combinedDetails,
                                    project_manager: project.project_manager || "N/A",
                                    customer_name: (project.contacts && project.contacts.length > 0)
                                        ? project.contacts[0].customer_name || "N/A"
                                        : "N/A",
                                    reopen_status: child.reopen_status || null
                                });


                                tasks.links.push({
                                    id: `link-${projectId}-${index}`,
                                    source: projectId,
                                    target: childId,
                                    type: "1"
                                });
                            });

                        });
                    } else {
                        console.warn("No projects found or backend error:", data);
                    }

                    // Clear old tasks and parse new
                    gantt.clearAll();
                    gantt.parse(tasks);
                })
                .catch(err => console.error("Error loading data:", err));
        }

        // Modal on task click (outside loadData to avoid duplicates)
        gantt.attachEvent("onTaskClick", function (id, e) {
            const task = gantt.getTask(id);
            document.getElementById("modalTitle").innerText = task.text;
            document.getElementById("modalContent").innerHTML = task.details || "No details available.";
            document.getElementById("projectModal").style.display = "block";
            return false;
        });

        // Initial load
        loadData();
    </script>
</body>

</html>