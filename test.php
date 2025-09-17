<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Project Gantt Chart</title>
    <link rel="stylesheet" href="libs/dhtmlxgantt.css">
    <script src="libs/dhtmlxgantt.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #1A1A55;
            --secondary-color: #2c3e50;
            --background-color: #f8fafc;
            --text-color: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --white: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --gray-light: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-sm: 4px;
            --transition: all 0.2s ease;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            font-size: 14px;
        }

        .gantt_task_line.highlighted {
            box-shadow: 0 0 10px 3px rgba(255, 215, 0, 0.9);
            border: 2px solid #FFD700 !important;
        }

        .container {
            padding: 20px;
            max-width: 1800px;
            margin-top: 70px;
        }

        .app-header {
            background-color: var(--primary-dark);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: var(--radius) var(--radius) 0 0;
            box-shadow: var(--shadow-md);
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 36px;
        }

        .app-title {
            color: var(--white);
            font-size: 18px;
            font-weight: 600;
            margin-left: 12px;
            padding-left: 12px;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }

        h2 {
            text-align: center;
            color: var(--secondary-color);
            font-weight: 600;
            margin: 20px 0;
            font-size: 24px;
        }

        /* --- UI Controls --- */
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: space-between;
            align-items: center;
            background-color: var(--white);
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label {
            font-weight: 500;
            color: var(--text-color);
            font-size: 13px;
            white-space: nowrap;
        }

        select,
        input[type="text"],
        input[type="date"] {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13px;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-color);
            height: 36px;
        }

        select:focus,
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }

        button {
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius-sm);
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        button:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        button:disabled {
            background-color: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background-color: var(--white);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--gray-light);
            border-color: #cbd5e1;
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .page-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #pageInfo {
            min-width: 100px;
            text-align: center;
            font-weight: 500;
            color: var(--text-light);
            font-size: 13px;
        }

        /* --- Gantt Chart Container --- */
        #gantt_here {
            width: 100%;
            height: calc(100vh - 220px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            background: var(--white);
            overflow: hidden;
        }

        /* --- Loading Overlay --- */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            z-index: 9999;
            color: var(--white);
            font-size: 18px;
            text-align: center;
            padding-top: 25vh;
            transition: opacity 0.3s;
            backdrop-filter: blur(4px);
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid var(--white);
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* --- Modal Styling --- */
        #projectModal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        #projectModal .content {
            background: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: var(--radius);
            width: 85%;
            max-width: 900px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px;
            background: var(--primary-dark);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--white);
            text-align: left;
            font-size: 20px;
        }

        #projectModal .close {
            font-size: 24px;
            font-weight: bold;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: var(--transition);
        }

        #projectModal .close:hover {
            color: var(--white);
        }

        .modal-body {
            padding: 24px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-section {
            margin-bottom: 24px;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        .modal-section h3 {
            margin-top: 0;
            color: var(--primary-dark);
            font-size: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        /* --- Gantt Specific Overrides --- */
        .gantt_grid_scale,
        .gantt_grid_data {
            border-right: 1px solid var(--border-color);
        }

        .gantt_grid_data .gantt_cell,
        .gantt_grid_scale .gantt_grid_head_cell {
            border-right: 1px solid var(--border-color);
            font-size: 13px;
        }

        .gantt_grid_data .gantt_row {
            border-bottom: 1px solid var(--border-color);
        }

        .gantt_grid_data .gantt_row.odd {
            background-color: #fafafa;
        }

        .gantt_grid_scale .gantt_grid_head_cell {
            background: #f8fafc;
            font-weight: 600;
            text-align: center;
            color: var(--secondary-color);
            padding: 8px 4px;
        }

        .gantt_grid_data .gantt_cell {
            padding: 8px 4px;
        }

        .gantt_task_progress {
            background: var(--primary-color) !important;
        }

        .gantt_tree_content {
            font-weight: 500;
            line-height: 20px;
        }

        .customer-tooltip {
            display: none;
            position: absolute;
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow-lg);
            z-index: 100;
            max-width: 320px;
            font-size: 13px;
            line-height: 1.5;
        }

        .customer-tooltip h4 {
            margin: 0 0 12px 0;
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 600;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .customer-tooltip p {
            margin: 6px 0;
        }

        .customer-cell {
            cursor: pointer;
            position: relative;
            padding: 4px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            line-height: 15px;
        }

        .customer-cell:hover {
            background-color: #f0f7ff;
        }

        /* Badge styles */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--text-color);
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .control-group {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 12px;
            }

            .control-group {
                flex-wrap: wrap;
            }

            #gantt_here {
                height: calc(100vh - 280px);
            }
        }

        .app-header {
            position: fixed;
            /* fixed at the top */
            top: 0;
            left: 0;
            width: 100%;
            background-color: #1A1A55;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            z-index: 1000;
            /* stay above other content */
        }

        .app-header .logo-container img {
            height: 40px;
        }

        .app-header .btn-secondary {
            background: #3498db;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .app-header .btn-secondary:hover {
            background: #2980b9;
        }

        /* Push page content down so it's not hidden under fixed header */
        .container {
            margin-top: 70px;
            /* adjust if header height changes */
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="app-header">
            <div class="logo-container">
                <img src="https://www.csaengineering.com.au/wp-content/uploads/2022/10/White-Logo.png" alt="Logo">
            </div>
            <button onclick="history.back()" class="btn-secondary">
                ← Go Back
            </button>
        </div>

        <h2>Project Gantt Chart</h2>

        <div class="controls">
            <div class="control-group">
                <label for="scaleSelect">View:</label>
                <select id="scaleSelect" onchange="setScale(this.value)">
                    <option value="week">Week</option>
                    <option value="month" selected>Month</option>
                    <option value="year">Year</option>
                </select>
            </div>

            <div class="control-group">
                <label for="pageSize">Show:</label>
                <select id="pageSize" onchange="changePageSize(this.value)">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div class="control-group page-controls">
                <button id="prevBtn" onclick="prevPage()">← Previous</button>
                <span id="pageInfo"></span>
                <button id="nextBtn" onclick="nextPage()">Next →</button>
            </div>

            <div class="control-group">
                <label for="searchBox">Search:</label>
                <input type="text" id="searchBox" placeholder="Project, ID, PM..." style="width:180px;">
                <button onclick="applySearch()">Search</button>
            </div>

            <div class="control-group">
                <label>Start: <input type="date" id="startDate"></label>
                <label>End: <input type="date" id="endDate"></label>
                <button onclick="applyDateFilter()">Filter</button>
                <button onclick="resetFilters()" class="btn-danger">Reset</button>
            </div>
        </div>

        <div id="loadingOverlay">
            <div class="spinner"></div>
            Loading projects...
        </div>
        <div id="gantt_here"></div>

        <div id="projectModal">
            <div class="content">
                <div class="modal-header">
                    <h2 id="modalTitle">Project Details</h2>
                    <span class="close"
                        onclick="document.getElementById('projectModal').style.display='none'">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="modalContent"></div>
                </div>
            </div>
        </div>

        <div id="customerTooltip" class="customer-tooltip"></div>
    </div>

    <script>
        // All your JavaScript code remains exactly the same
        // Only the UI has been enhanced
        let currentPage = 1;
        let pageSize = 50;
        let totalItems = 0;
        let searchQuery = "";
        let filterStartDate = "";
        let filterEndDate = "";

        function showLoading() { document.getElementById("loadingOverlay").style.display = "block"; }
        function hideLoading() { document.getElementById("loadingOverlay").style.display = "none"; }

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
            red: "#ff0000",
            navy: "#1A1A55",
            green: "#188918",
            orange: "#FFA500",
            yellow: "#FFFF00",
            purple: "#8B008B",
            white: "#fefdfd",
            gray: "#bfbfbf"
        };

        // Configure columns
        gantt.config.columns = [
            { name: "text", label: "Name", tree: true, width: 200 },
            {
                name: "projectId",
                label: "Project ID",
                align: "center",
                width: 180,
                template: function (task) {
                    let html = task.projectId || "";

                    // Reopen badge
                    if (task.reopen_status) {
                        html += `<span class="badge badge-danger" style="margin-left:5px;">
        ${task.reopen_status}
    </span>`;
                    }

                    // Subproject status badge (red) — only for subprojects / child rows
                    if (task.subproject_status) {
                        html += `<span class="badge badge-danger" style="margin-left:5px;">
        S${task.subproject_status}
    </span>`;
                    }
                    return html;
                }
            }
            ,
            { name: "project_manager", label: "Project Manager", align: "center", width: 150 },
            {
                name: "customer_name",
                label: "Customer",
                align: "center",
                width: 150,
                template: function (task) {
                    // Show tooltip for all tasks but prevent multiple tooltips
                    return `<div class="customer-cell" onmouseover="showCustomerTooltip(event, '${task.id}')" onmouseout="hideCustomerTooltip()">
            ${task.customer_name || "N/A"}
        </div>`;
                }
            }
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
        // Show customer tooltip on hover
        function showCustomerTooltip(event, taskId) {
            const task = gantt.getTask(taskId);

            // If this task doesn't have customer details but has a parent, try the parent
            let customerTask = task;
            if ((!task.customer_details || task.customer_details === "N/A") && task.parent) {
                customerTask = gantt.getTask(task.parent);
            }

            // If still no customer details, try to find the root parent
            if ((!customerTask.customer_details || customerTask.customer_details === "N/A") && customerTask.parent) {
                let rootTask = customerTask;
                while (rootTask.parent) {
                    rootTask = gantt.getTask(rootTask.parent);
                }
                customerTask = rootTask;
            }

            if (!customerTask || !customerTask.customer_details || customerTask.customer_details === "N/A") return;

            // Hide any existing tooltip first to prevent multiple tooltips
            hideCustomerTooltip();

            const tooltip = document.getElementById("customerTooltip");
            tooltip.innerHTML = customerTask.customer_details;
            tooltip.style.display = "block";
            tooltip.style.left = (event.pageX + 10) + "px";
            tooltip.style.top = (event.pageY + 10) + "px";

            // Add a small delay to prevent tooltip from showing again immediately
            tooltip.dataset.lastShown = Date.now();
        }

        function hideCustomerTooltip() {
            const tooltip = document.getElementById("customerTooltip");
            // Only hide if it wasn't just shown (to prevent flickering)
            if (!tooltip.dataset.lastShown || (Date.now() - parseInt(tooltip.dataset.lastShown)) > 100) {
                tooltip.style.display = "none";
            }
        }

        // Format contact details for display
        function formatContactDetails(contacts) {
            if (!contacts || contacts.length === 0) return null;

            let seen = new Set();
            let html = "<h4>Customer Details</h4>";

            contacts.forEach(c => {
                if (seen.has(c.customer_name)) return; // skip duplicates
                seen.add(c.customer_name);

                html += `
            <p><strong>${c.customer_name || "N/A"}</strong></p>
            <p>Name: ${c.contact_name || "N/A"}</p>
            <p>Email: ${c.contact_email || "N/A"}</p>
            <p>Phone: ${c.contact_phone_number || "N/A"}</p>
            <p>Address: ${c.address || "N/A"}</p>
            <hr>
        `;
            });

            return html;
        }


        // Fetch backend data
        function loadData() {
            showLoading();
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

                            // Format customer details for the tooltip
                            const customerDetails = formatContactDetails(project.contacts);

                            // Project Info
                            // Badge for reopen_status
                            let reopenBadge = "";
                            if (project.reopen_status) {
                                reopenBadge = `<span class="badge badge-danger" style="margin-left:5px;">
        ${project.reopen_status}
    </span>`;
                            }

                            // Project Info with badge
                            let projectInfoHTML = `
<div class="modal-section">
    <h3>Project Information</h3>
    <p><strong>Project ID:</strong> ${project.id || "N/A"} ${reopenBadge}</p>
    <p><strong>Description:</strong> ${project.project_details || project.subproject_details || "N/A"}</p>
    <p><strong>Start Date:</strong> ${project.start ? new Date(project.start).toDateString() : "N/A"}</p>
    <p><strong>Expected End Date:</strong> ${project.end ? new Date(project.end).toDateString() : "N/A"}</p>
    <p><strong>Project Manager:</strong> ${project.project_manager || "N/A"}</p>
    <p><strong>Engineer:</strong> ${project.assign_to || "N/A"}</p>
    <p><strong>Team:</strong> ${project.p_team || "N/A"}</p>
    <p><strong>Status:</strong> ${project.urgency || "N/A"}</p>
    <p><strong>State:</strong> ${project.state || "N/A"}</p>
</div>
`;


                            // Receivable Details
                            let receivableHTML = `
                            <h3>Receivable Details</h3>
                            <table>
                                <thead>
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
                            <table>
                                <thead>
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

                            // Merge everything (contact details removed from modal)
                            const fullDetailsHTML = `
                            ${projectInfoHTML}
                            <div class="modal-section">${receivableHTML}</div>
                            <div class="modal-section">${payableHTML}</div>
                        `;

                            // Parent task
                            tasks.data.push({
                                id: projectId,
                                text: project.name,
                                start_date: start,
                                end_date: end,
                                progress: 0,
                                open: false,
                                projectId: projectId,
                                urgency: project.urgency,
                                details: fullDetailsHTML,
                                project_manager: project.project_manager || "N/A",
                                customer_name: (project.contacts && project.contacts.length > 0)
                                    ? project.contacts[0].customer_name || "N/A"
                                    : "N/A",
                                customer_details: customerDetails, // This will be shown in the tooltip
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
                                    childReopenBadge = `<span class="badge badge-danger" style="margin-left:5px;">
        ${child.reopen_status}
    </span>`;
                                }

                                const combinedDetails = `
            <strong>Subproject:</strong> ${child.name || `Subproject ${index + 1}`} ${childReopenBadge}<br>
            Start: ${childStart || "N/A"}<br>
            End: ${childEnd || "N/A"}<br>
            Details: ${child.subproject_details || "N/A"}
            Status: ${child.status || "N/A"}<br>
            Urgency: ${child.urgency || "N/A"}<br>
            <hr>
            ${receivableHTML}
            ${payableHTML}
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
                                    reopen_status: child.reopen_status || null,
                                    customer_details: null,
                                    subproject_status: child.status || null
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
                .catch(err => console.error("Error loading data:", err))
                .finally(() => hideLoading());
        }

        // Only open modal when clicking on the bar area (not grid)
        gantt.attachEvent("onTaskClick", function (id, e) {
            const taskArea = e.target.closest(".gantt_task_line");
            if (taskArea) {
                const task = gantt.getTask(id);
                document.getElementById("modalTitle").innerText = task.text;
                document.getElementById("modalContent").innerHTML = task.details || "No details available.";
                document.getElementById("projectModal").style.display = "block";
                return false; // stop default select
            }
            return true; // let grid clicks pass through
        });
        // When clicking grid (columns) → scroll + highlight bar
        gantt.attachEvent("onGridClick", function (id, e) {
            gantt.showTask(id);   // scroll chart to bar
            highlightTaskBar(id); // highlight effect
            return true;
        });

        function highlightTaskBar(taskId) {
            const taskNode = gantt.getTaskNode(taskId);
            if (taskNode) {
                taskNode.classList.add("highlighted");
                setTimeout(() => taskNode.classList.remove("highlighted"), 2000);
            }
        }





        // Initial load
        loadData();
    </script>
</body>

</html>