<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Project Gantt Chart</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsgantt-improved/dist/jsgantt.css" />
  <script src="https://cdn.jsdelivr.net/npm/jsgantt-improved/dist/jsgantt.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }

    /* Layout: Name column 20%, chart 80% */
    #GanttChartDIV {
      width: 80%;
      float: right;
      height: 500px;
      overflow-x: auto;
    }

    #GanttNameColumn {
      width: 20%;
      float: left;
    }

    /* Hide all default columns except Name */
    .gres {
      display: table-cell !important;
    }

    .gres .gtaskheading {
      font-weight: bold;
    }

    .gres .gtaskheading::after {
      content: "Project ID";
      /* rename Resource column */
    }

    .gdur,
    .gcomp,
    .gstart,
    .gend,
    .gcaption {
      display: none !important;
    }

    /* Style Name column */
    .gname {
      width: 100% !important;
      font-weight: bold;
      font-size: 14px;
      color: #0004ffff;
    }
  </style>
</head>

<!-- Project Detail Modal -->
<div id="projectModal" class="modal"
  style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color: rgba(0,0,0,0.5);">
  <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:60%;">
    <span onclick="document.getElementById('projectModal').style.display='none'"
      style="float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
    <h2 id="modalTitle">Project Details</h2>
    <div id="modalContent"></div>
  </div>
</div>


<body>

  <h2>Project Gantt Chart</h2>

  <div id="GanttNameColumn"></div>
  <div id="GanttChartDIV"></div>

  <script>
    // Map urgency text to colors
    const urgencyColors = {
      red: "#ff0000ff",       // Very Urgent
      navy: "#1A1A55",        // Completed
      green: "#188918",       // In Progress
      orange: "#FFA500",      // Urgent
      yellow: "#FFFF00",      // On Hold
      purple: "#8B008B",      // Closed
      white: "#fefdfd",       // Waiting
      gray: "#bfbfbf"         // Not started
    };


    const g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'day');

    if (g) {
      g.setOptions({
        vCaptionType: 'Complete',
        vQuarterColWidth: 36,
        vDateTaskDisplayFormat: 'day dd month yyyy',
        vFormatArr: ['Day', 'Week', 'Month', 'Year'],
        vShowRes: 0,
        vShowDur: 0,
        vShowComp: 0,
        vShowStartDate: 0,
        vShowEndDate: 0
      });
      g.setAdditionalHeaders({
        projectId: {
          title: "Project ID",
          width: 80
        }
      });


      fetch('http://localhost:5000/gantt-data')
        .then(response => response.json())
        .then(data => {
          const tasks = [];

          data.projects.forEach(project => {
            const projectId = project.id;
            const projectName = project.name;
            const start = project.start?.split("T")[0];
            const end = project.end?.split("T")[0];

            // Build contact details (if any)
            let contactDetailsHTML = "";
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
              contactDetailsHTML = "<em>No contact details available</em>";
            }

            // Parent (group) task
            tasks.push({
              pID: projectId,
              projectId: projectId,
              pName: projectName,
              pStart: start,
              pEnd: end,
              pComp: 0,
              pGroup: 1,
              pParent: 0,
              pOpen: 1,
              pClass: "ggroupblack",
              pColor: urgencyColors[project.urgency] || "#008000", // <-- color applied here
              pNotes: contactDetailsHTML
            });




            // Add children (subprojects)
            project.children?.forEach((child, index) => {
              const childStart = child.start?.split("T")[0];
              const childEnd = child.end?.split("T")[0];

              tasks.push({
                pID: `${projectId}-${index}`,
                pName: child.name || `Subproject ${index + 1}`,
                pStart: childStart,
                pEnd: childEnd,
                pComp: 0,
                pGroup: 0,
                pParent: projectId,
                pOpen: 1,
                pClass: "",
                pColor: urgencyColors[child.urgency] || "#000000", // <-- color applied here
                pNotes: `
    <strong>${child.name}</strong><br>
    Start: ${childStart || "N/A"}<br>
    End: ${childEnd || "N/A"}<br>
    Status: ${child.status || "N/A"}<br>
    Urgency: ${child.urgency || "N/A"}<br>
    Details: ${child.subproject_details || "N/A"}
  `
              });


            });
          });


          tasks.forEach(t => g.AddTaskItemObject(t));

          g.Draw();
          g.DrawDependencies();

          // Open modal on click
          g.setEvents({
            taskClick: function (task) {
              document.getElementById("modalTitle").innerText = task.getName();
              document.getElementById("modalContent").innerHTML = task.getNotes() || "No details available.";
              document.getElementById("projectModal").style.display = "block";
              return false; // stop default
            }
          });

          // Remove any title attributes inside the Gantt (prevents native hover tooltips)
          ['#GanttChartDIV', '#GanttNameColumn'].forEach(selector => {
            document.querySelectorAll(selector + ' [title]').forEach(el => el.removeAttribute('title'));
          });

          // Keep removing any title attributes that might be added later
          const ganttContainer = document.getElementById('GanttChartDIV');
          if (ganttContainer) {
            const observer = new MutationObserver(mutations => {
              mutations.forEach(m => {
                m.addedNodes.forEach(node => {
                  if (node.nodeType === 1) {
                    if (node.hasAttribute && node.hasAttribute('title')) node.removeAttribute('title');
                    node.querySelectorAll && node.querySelectorAll('[title]').forEach(el => el.removeAttribute('title'));
                  }
                });
              });
            });
            observer.observe(ganttContainer, { childList: true, subtree: true });
          }
        })
        .catch(error => {
          console.error("Error loading Gantt data:", error);
        });
    }
  </script>

</body>

</html>