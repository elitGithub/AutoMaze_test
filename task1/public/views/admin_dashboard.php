<div class="container mx-auto py-8 sm:py-10 md:py-12 lg:py-16 xl:py-20 2xl:py-24 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 rounded-lg shadow-lg text-center max-w-[80%] max-h-[80vh]">
    <h1 class="text-3xl font-bold mb-4 text-center">Bug Tracker</h1>
    <div class="flex justify-center">
        <table id="bugsTable" class="min-w-full bg-white display">
            <thead>
            <tr>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Submitted By</th>
                <th class="px-4 py-2">Title</th>
                <th class="px-4 py-2">Urgency</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Set Status</th>
                <th class="px-4 py-2">Add Comment</th>
            </tr>
            </thead>
            <tbody id="bugsTableBody">
            <!-- Table rows will be handled by DataTables -->
            </tbody>
        </table>
    </div>
</div>
<div id="success-snackbar" class="fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-md hidden">
    <p>Success! Your action was completed.</p>
</div>

{{viewScripts}}