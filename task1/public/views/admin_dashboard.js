$(document).ready(async function() {
    const table = $('#bugsTable').DataTable({
        ajax: {
            url: "/admin/getBugs",
            dataSrc: function(json) {
                return json.data.bugs;
            }
        },
        columns: [
            { "data": "id" },
            { "data": "submitted_by" },
            { "data": "title" },
            { "data": "urgency" },
            { "data": "status" },
            {
                "data": "id",
                "render": function(data, type, row) {
                    return `<select class="border border-gray-300 rounded px-2 py-1 bg-gray-700 text-white update-bug-status-magic" data-update-bug="${data}">
                                <option value="Open" ${row.status === 'Open' ? 'selected' : ''}>Open</option>
                                <option value="In Progress" ${row.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                                <option value="Resolved" ${row.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                            </select>`;
                }
            },
            {
                "data": "id",
                "render": function(data, type, row) {
                    return `<textarea class="add-comment-area border border-gray-300 rounded px-2 py-1 bg-gray-700 text-white" data-bug-id="${data}" placeholder="Write your comment..."></textarea>`;
                }
            }
        ],
        pagingType: "simple",
        initComplete: function(settings, json) {
            // Apply Tailwind styles to search and length elements
            $('div.dataTables_length select').addClass('border border-gray-300 rounded px-2 py-1 bg-gray-700 text-white');
            $('div.dataTables_filter input').addClass('border border-gray-300 rounded px-2 py-1 bg-gray-700 text-white');
            $('div.dataTables_paginate a').addClass('bg-gray-800 text-white border border-gray-300 rounded px-2 py-1 mx-1');
            $('div.dataTables_info').addClass('text-gray-400');
            this.on('change', '.update-bug-status-magic', function() {
                const select = $(this);
                const bugId = select.data('update-bug');
                const newStatus = select.val();
                const rowElement = select.closest('tr');
                const rowIndex = table.row(rowElement).index();

                updateBug(bugId, newStatus, function(success) {
                    if (success) {
                        // Update the status column in the DataTable
                        table.cell(rowIndex, 4).data(newStatus).draw(false); // Assuming status column is the fifth column
                    }
                });
            });
            this.on('input', '.add-comment-area', function() {
                const textarea = $(this);
                const bugId = textarea.data('bug-id');
                const comment = textarea.val();

                // Set a timeout to delay the sending of the request
                clearTimeout(textarea.data('timeout'));
                textarea.data('timeout', setTimeout(function() {
                    addCommentToBug(bugId, comment);
                }, 1000)); // 1-second delay
            });
        }
    });

    setInterval(function() {
        table.ajax.reload(null, false); // user paging is not reset on reload
    }, 30000);
});

async function updateBug(id, status, callback) {
    const res = await apiFetch('updateBug', 'POST', { id, status });
    const response = checkResponse(res);
    callback(res.success);
    showSnackbar();
}
function showSnackbar(message) {
    const snackbar = $('#success-snackbar');
    snackbar.text(message).removeClass('hidden');
    setTimeout(() => snackbar.addClass('hidden'), 3000);
}

async function addCommentToBug(id, comment) {
    const res = await apiFetch('actions?module=comments&action=addCommentToBug', 'POST', { id, comment });
    const response = checkResponse(res);
    showSnackbar();
}