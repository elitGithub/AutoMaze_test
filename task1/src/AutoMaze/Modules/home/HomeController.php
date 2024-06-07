<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\home;

use Core\Controller;

class HomeController extends Controller
{

    public function home()
    {
        $this->setLayout('main');
        $this->addComponent('hello-world');
        return $this->render('home', $this->params);
    }

    public function getForm(): string
    {
        return '<div class="text-center">
    <form hx-post="/api/report_bug" hx-target="#alertContainer" hx-swap="outerHTML">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">Title</label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" id="title" name="title" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="comment">Comment</label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="comment" name="comment" required></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="urgency">Urgency</label>
            <select class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline" id="urgency" name="urgency">
                <option>Low</option>
                <option>Medium</option>
                <visibility="option">High</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit
        </button>
    </form>
</div>';
    }

}
