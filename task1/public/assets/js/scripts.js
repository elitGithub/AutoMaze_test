function enforceNumberOnlyInput(event) {
    // Allow control keys (backspace, tab, delete, arrow keys, etc.)
    if (event.ctrlKey || event.altKey || event.metaKey ||
        [8, 9, 13, 27, 46, 37, 38, 39, 40].includes(event.keyCode)) {
        return;
    }
    // Prevent non-numeric keys
    if (!/\d/.test(event.key)) {
        event.preventDefault();
    }
}

// Function to get CSRF token
async function getCSRFToken() {
    const res = await fetch(`/api/token`);
    const json = await res.json();
    const response = checkResponse(json);
    return response.data.token;
}

// Centralized Fetch function
async function apiFetch(url, method = 'GET', data = null) {
    const csrfToken = await getCSRFToken();

    const defaultHeaders = {
        'X-CSRF-TOKEN': csrfToken,
    };

    const options = {
        method,
        headers: defaultHeaders,
    };

    if (data) {
        if (data instanceof FormData) {
            // If data is FormData, do not set Content-Type header, let the browser set it
            options.body = data;
        } else {
            // Otherwise, treat it as JSON
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    const response = await fetch(`/api/${url}`, options);

    if (!response.ok) {
        // Handle errors
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return await checkResponse(await response.json());
}


function checkResponse(response) {
    if (!('success' in response) || !response.success) {
        throw new Error(`Error: ${ response?.message || 'Unknown error' }`);
    }
    return response;
}

async function apiAjax(url, method = 'GET', data = null) {
    const csrfToken = await getCSRFToken();

    const defaultHeaders = {
        'X-CSRF-TOKEN': csrfToken,
    };

    const options = {
        url: `/api/${url}`,
        method: method,
        headers: defaultHeaders,
        dataType: 'json',
    };

    if (data) {
        if (data instanceof FormData) {
            options.contentType = false;
            options.processData = false;
            options.data = data;
        } else {
            options.contentType = 'application/json';
            options.data = JSON.stringify(data);
        }
    }

    try {
        const response = await $.ajax(options);
        return await checkResponse(response);
    } catch (error) {
        throw new Error(`HTTP error! error: ${error}`);
    }
}

let conn;
const reconnectInterval = 5000; // Interval to attempt reconnection

function connect() {
    conn = new WebSocket('ws://localhost:8080');

    conn.onopen = function(e) {
        console.log("Connection established! Listening for data...");
    };

    conn.onmessage = function(e) {
        console.log('Received message from server:', e.data);
        performActionBasedOnSMessage(e.data);
    };

    conn.onerror = function(e) {
        console.error("WebSocket error:", e);
        // The connection will be closed automatically if there is an error
    };

    conn.onclose = function(e) {
        console.log("Connection closed:", e);
        // Try to reconnect if the connection is closed
        reconnect();
    };
}

function performActionBasedOnSMessage(message) {
    // Here, you would handle the message and trigger any client-side actions
    console.log("Performing action with:", message);
    // Example: if your messages are in JSON format
    try {
        const data = JSON.parse(message);

        let storedId = localStorage.getItem('id');
        if (data.event === 'commentAdded' && data.data.submitted_by === storedId) {
            socketResponseSnack(`A new comment was added to your bug: ${data.data.content}`);
        }

        if (data.event === 'bugStatusUpdate' && data.data.bugInfo.submitted_by === storedId) {
            socketResponseSnack(`You bug status was changed to: ${data.data.status}`);
        }
        // Perform actions based on 'data'
    } catch (error) {
        console.error(error);
        console.error("Error parsing message:", message);
    }
}

function reconnect() {
    console.log("Attempting to reconnect...");
    setTimeout(connect, reconnectInterval);
}

function socketResponseSnack(message) {
    const div = document.createElement('div');
    div.id = 'socket-snackbar';
    div.classList.add('fixed');
    div.classList.add('bottom-4');
    div.classList.add('right-4');
    div.classList.add('bg-green-500');
    div.classList.add('text-white');
    div.classList.add('p-4');
    div.classList.add('rounded');
    div.classList.add('shadow-md');
    const p = document.createElement('p');
    p.textContent = message;
    div.appendChild(p);
    document.querySelector('body').appendChild(div);
    setTimeout(() => div.remove(), 5000);
}

// Initiate the first connection
connect();