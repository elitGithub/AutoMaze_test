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
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// Centralized Fetch function
async function apiFetch(url, method = 'GET', data = null) {
    const csrfToken = getCSRFToken();

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
    const csrfToken = getCSRFToken();

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


async function getCitiesList() {
    const response = await apiFetch('citiesList');
    return response.data.cities;
}

async function getCategoriesList() {
    const response = await apiFetch('categoriesList');
    return response.data.categories;
}
