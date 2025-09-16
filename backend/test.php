<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gantt Chart</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl p-6 md:p-10 border border-gray-200">

        <!-- Header -->
        <h1 class="text-3xl md:text-4xl font-extrabold text-center text-gray-800 mb-2">Project Gantt Chart</h1>
        <p class="text-center text-gray-500 mb-6 md:mb-8">Visualize projects, subprojects, and their timelines.</p>

        <!-- Control and Filter Section -->
        <div class="flex flex-col lg:flex-row items-center justify-between space-y-4 lg:space-y-0 lg:space-x-4 mb-6">
            <div class="flex-1 w-full flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                <!-- Search Input -->
                <input type="text" id="searchInput" placeholder="Search projects..." class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                
                <!-- Date Range Filters -->
                <div class="flex flex-1 space-x-2">
                    <input type="date" id="startDate" class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                    <input type="date" id="endDate" class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex space-x-2 w-full lg:w-auto">
                <button id="applyFiltersBtn" class="w-full lg:w-auto px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300">Apply Filters</button>
                <button id="resetBtn" class="w-full lg:w-auto px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300">Reset</button>
            </div>
        </div>

        <!-- Pagination & Total Count Section -->
        <div class="flex flex-col sm:flex-row items-center justify-between mb-6 space-y-4 sm:space-y-0">
            <div class="text-sm text-gray-500">
                Showing <span id="projectCount" class="font-bold text-gray-700">0</span> of <span id="totalCount" class="font-bold text-gray-700">0</span> projects.
            </div>
            <div class="flex space-x-2">
                <button id="prevPageBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                <span id="currentPageSpan" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-bold">1</span>
                <button id="nextPageBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
            </div>
        </div>

        <!-- Gantt Chart Container -->
        <div id="chart_div" class="w-full h-[600px] border border-gray-300 rounded-lg flex items-center justify-center text-gray-500">
            <span id="loadingMessage">Loading chart data...</span>
        </div>

    </div>

    <!-- Google Charts Library -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        // Google Charts Setup
        google.charts.load('current', {'packages':['gantt']});
        google.charts.setOnLoadCallback(init);

        // State variables
        let currentPage = 1;
        const limit = 50;
        let searchQuery = '';
        let startDate = '';
        let endDate = '';
        let totalProjects = 0;

        // DOM elements
        const chartDiv = document.getElementById('chart_div');
        const loadingMessage = document.getElementById('loadingMessage');
        const searchInput = document.getElementById('searchInput');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const resetBtn = document.getElementById('resetBtn');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const currentPageSpan = document.getElementById('currentPageSpan');
        const projectCountSpan = document.getElementById('projectCount');
        const totalCountSpan = document.getElementById('totalCount');

        function init() {
            // Initial data fetch
            fetchData();

            // Event Listeners
            applyFiltersBtn.addEventListener('click', () => {
                currentPage = 1;
                searchQuery = searchInput.value;
                startDate = startDateInput.value;
                endDate = endDateInput.value;
                fetchData();
            });

            resetBtn.addEventListener('click', () => {
                currentPage = 1;
                searchQuery = '';
                startDate = '';
                endDate = '';
                searchInput.value = '';
                startDateInput.value = '';
                endDateInput.value = '';
                fetchData();
            });

            prevPageBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    fetchData();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const maxPages = Math.ceil(totalProjects / limit);
                if (currentPage < maxPages) {
                    currentPage++;
                    fetchData();
                }
            });
        }

        async function fetchData() {
            chartDiv.innerHTML = `<span id="loadingMessage" class="text-gray-500">Loading chart data...</span>`;
            loadingMessage.classList.remove('hidden');
            
            // Build the URL with query parameters
            let url = `/gantt-data?limit=${limit}&page=${currentPage}`;
            if (searchQuery) url += `&search=${encodeURIComponent(searchQuery)}`;
            if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
            if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (response.ok) {
                    totalProjects = data.total;
                    updatePaginationUI(data.count, data.total);
                    drawChart(data.projects);
                } else {
                    chartDiv.innerHTML = `<span class="text-red-500 font-semibold">${data.error || 'Failed to fetch data.'}</span>`;
                    updatePaginationUI(0, 0);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
                chartDiv.innerHTML = `<span class="text-red-500 font-semibold">Error: Could not connect to the backend.</span>`;
                updatePaginationUI(0, 0);
            }
        }

        function updatePaginationUI(count, total) {
            projectCountSpan.textContent = count;
            totalCountSpan.textContent = total;
            currentPageSpan.textContent = currentPage;
            
            const maxPages = Math.ceil(total / limit);
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage >= maxPages || total === 0;
        }

        function drawChart(projects) {
            // Remove loading message
            chartDiv.innerHTML = '';
            
            // Create data table
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Task ID');
            data.addColumn('string', 'Task Name');
            data.addColumn('string', 'Resource');
            data.addColumn('date', 'Start Date');
            data.addColumn('date', 'End Date');
            data.addColumn('number', 'Duration');
            data.addColumn('number', 'Percent Complete');
            data.addColumn('string', 'Dependencies');
            data.addColumn({ type: 'string', role: 'style' }); // Add a style column for color-coding

            // Add rows for each project and subproject
            projects.forEach(project => {
                // Format dates for display
                const formattedStartDate = project.start ? new Date(project.start).toLocaleDateString() : 'N/A';
                const formattedEndDate = project.end ? new Date(project.end).toLocaleDateString() : 'N/A';
                // Combine project details into the Task Name column
                const taskName = `ID: ${project.id} - ${project.name} (${project.state || 'N/A'}) - ${formattedStartDate} to ${formattedEndDate}`;

                // Add main project row
                data.addRow([
                    String(project.id),
                    taskName,
                    project.project_manager,
                    new Date(project.start),
                    new Date(project.end),
                    null,
                    getCompletionPercentage(project),
                    null,
                    getBarColor(project)
                ]);

                // Add subprojects as children
                project.children.forEach(subproject => {
                    const formattedSubStartDate = subproject.start ? new Date(subproject.start).toLocaleDateString() : 'N/A';
                    const formattedSubEndDate = subproject.end ? new Date(subproject.end).toLocaleDateString() : 'N/A';
                    const subTaskName = `ID: ${subproject.id} - ${subproject.name} (${subproject.status || 'N/A'}) - ${formattedSubStartDate} to ${formattedSubEndDate}`;

                    data.addRow([
                        String(subproject.id),
                        subTaskName,
                        subproject.assign_to,
                        new Date(subproject.start),
                        new Date(subproject.end),
                        null,
                        getCompletionPercentage(subproject),
                        String(project.id), // Dependency to the parent project
                        getBarColor(subproject)
                    ]);
                });
            });

            // Options for the chart
            const options = {
                height: Math.min(600, (data.getNumberOfRows() * 41) + 50),
                gantt: {
                    defaultStartDate: new Date(new Date().getFullYear(), 0, 1),
                    barHeight: 20,
                    trackHeight: 30,
                    percentEnabled: true
                },
                backgroundColor: '#f9fafb'
            };

            // Instantiate and draw the chart
            const chart = new google.visualization.Gantt(chartDiv);
            chart.draw(data, options);
        }

        // Helper function to calculate completion percentage
        function getCompletionPercentage(task) {
            if (task.state === 'Completed' || task.status === 'Completed') {
                return 100;
            } else if (task.state === 'In Progress' || task.status === 'In Progress') {
                return 50; // A generic representation
            }
            return 0; // Not Started
        }

        // Helper function to get bar color based on state
        function getBarColor(task) {
            const state = task.state || task.status;
            switch (state) {
                case 'Not Started':
                    return '#dc2626'; // Red
                case 'In Progress':
                    return '#facc15'; // Yellow
                case 'Completed':
                    return '#22c55e'; // Green
                default:
                    return '#64748b'; // Gray
            }
        }
    </script>
</body>
</html>
