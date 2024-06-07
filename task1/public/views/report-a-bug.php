<?php

use Core\Storm;

$csrfToken = Storm::getStorm()->security->generateCsrfToken();
Storm::getStorm()->session->addValue('csrf_token', $csrfToken);
?>
<div id="mainContainer">
    <section class="bg-white dark:bg-gray-900 rounded max-w-4xl">
        <div class="py-8 lg:py-16 px-4 mx-auto max-w-full w-screen">
            <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-center text-gray-900 dark:text-white">$(translation):$report_a_bug</h2>
            <p class="mb-12 lg:mb-16 font-light text-center text-gray-500 dark:text-gray-400 sm:text-xl">Looking forward to hearing from you.</p>
            <form action="/api/report_bug" method="post" class="space-y-8" hx-post="/api/report_bug" hx-target="#mainContainer" hx-swap="innerHTML">
                <div>
                    <label for="title" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Title</label>
                    <input type="text" id="title" name="title" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light" placeholder="Title..." required>
                </div>
                <div>
                    <label for="urgency" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Urgency</label>
                    <select class="block p-3 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light" id="urgency" name="urgency">
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="comment" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">Comment</label>
                    <textarea id="comment" name="comment" rows="6" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg shadow-sm border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Leave a comment..."></textarea>
                </div>
                <input type="hidden" name="form-token" value="<?php echo $csrfToken ?>">
                <input type="hidden" id="user_id" name="submitted_by">
                <button type="submit" class="py-3 px-5 text-sm font-medium text-center text-white rounded-lg bg-primary-700 sm:w-fit hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Report a bug</button>
            </form>
            <div id="flashMessage"></div>
        </div>
    </section>
</div>

{{viewScripts}}