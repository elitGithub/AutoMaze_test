<div id="mainContainer" class="text-center">
    <h1 class="text-2xl font-bold mb-4">Hello World</h1>
    <button hx-get="/getForm" hx-target="#mainContainer" hx-swap="outerHTML" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Report a Bug
    </button>
</div>

<div id="alertContainer"></div>