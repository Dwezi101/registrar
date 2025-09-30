<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registrar Calendar</title>

    <!-- ✅ FullCalendar & Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden; /* prevent scroll */
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            color: #333;
        }
        #calendar {
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 20px;
            background: #fff;
            box-sizing: border-box;
        }
        .fc {
            height: 100% !important; /* make sure FullCalendar fills */
        }
        .fc-daygrid-day-number,
        .fc-col-header-cell-cushion {
            color: #333 !important;
            text-decoration: none !important;
            cursor: pointer !important;
        }
        /* Default Event Style */
        .fc-daygrid-event {
            border: none !important;
            border-radius: 8px !important;
            padding: 4px 6px !important;
            font-weight: 500 !important;
            font-size: 13px !important;
            text-decoration: none !important;
            cursor: default !important;
        }

        /* 🟡 Pending */
        .fc-daygrid-event.fc-event.pending,
        .fc-daygrid-event.fc-event.pending * {
            background-color: #facc15 !important; /* yellow */
            color: #000 !important; /* force black text */
        }

        /* 🟢 Released */
        .fc-event.released {
            background-color: #22c55e !important; /* green */
            color: #fff !important;
        }

        /* Hover Effect */
        .fc-daygrid-event:hover {
            filter: brightness(1.1);
        }
        .modal-header {
            background: #6366f1;
            color: #fff;
        }
        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 8px;
        }
        .badge-pending {
            background-color: #facc15;
            color: #000;
        }
        .badge-released {
            background-color: #22c55e;
        }
    </style>
</head>
<body>
    <div id="calendar"></div>

    <!-- 📅 Requests Modal -->
    <div class="modal fade" id="requestsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Requests on <span id="modalDate"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="modalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 📝 Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Update Request</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="updateForm">
              <input type="hidden" name="id" id="editId">
              <div class="mb-3">
                <label for="editStatus" class="form-label">Status</label>
                <select id="editStatus" name="status" class="form-select">
                  <option value="Pending">Pending</option>
                  <option value="Released">Released</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="editDate" class="form-label">Release Date</label>
                <input type="date" id="editDate" name="release_date" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Changes</button>
            </form>
          </div>
        </div>
      </div>
    </div>

<script>
let calendar;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: {
            url: 'fetch_events.php', // ✅ dynamic source
            method: 'GET',
            failure: function() {
                alert('There was an error fetching events!');
            }
        },
        dayMaxEvents: 2,
        dateClick: function(info) {
            var selectedDate = info.dateStr;

            // 🔄 Fetch latest events dynamically
            fetch('fetch_events.php')
              .then(res => res.json())
              .then(allEvents => {
                  let list = allEvents.filter(e => e.date === selectedDate);

                  let modalBody = document.getElementById('modalBody');
                  let modalDate = document.getElementById('modalDate');
                  modalDate.textContent = selectedDate;

                  if (list.length > 0) {
                      let html = "<ul class='list-group'>";
                      list.forEach(ev => {
                          let badgeClass = ev.status === 'Released' ? 'badge-released' : 'badge-pending';
                          html += `
                            <li class='list-group-item'>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                <strong>${ev.title}</strong><br>
                                Contact: ${ev.contact}<br>
                                Status: <span class="badge ${badgeClass}">${ev.status}</span>
                                </div>
                                <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    onclick="openEditModal(${ev.id}, '${ev.status}', '${ev.date}')">
                                    Manage
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteRequest(${ev.id})">
                                    Delete
                                </button>
                                </div>
                            </div>
                            </li>`;
                      });
                      html += "</ul>";
                      modalBody.innerHTML = html;
                  } else {
                      modalBody.innerHTML = "<p>No requests scheduled for this date.</p>";
                  }

                  var myModal = new bootstrap.Modal(document.getElementById('requestsModal'));
                  myModal.show();
              });
        }
    });

    calendar.render();
});

// 🗑️ delete request
function deleteRequest(id) {
    if (confirm("Are you sure you want to delete this request?")) {
        fetch('delete_request.php', {
            method: 'POST',
            body: new URLSearchParams({ id: id })
        })
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            calendar.refetchEvents();
            bootstrap.Modal.getInstance(document.getElementById('requestsModal')).hide();
        });
    }
}

// 📝 open edit modal
function openEditModal(id, status, date) {
    document.getElementById('editId').value = id;
    document.getElementById('editStatus').value = status;
    document.getElementById('editDate').value = date;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// 💾 handle update form
document.getElementById('updateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('update_request.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.text())
    .then(data => {
        alert(data);
        // 🔄 Refresh events after update
        calendar.refetchEvents();
        bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        bootstrap.Modal.getInstance(document.getElementById('requestsModal')).hide();
    });
});
</script>
</body>
</html>
