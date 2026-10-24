/**
 * DNS sync page: AJAX actions over one zone each, strictly one request at a time.
 * Each response carries the re-rendered zone <tbody>; all markup stays in PHP.
 */
(function () {
    'use strict';

    var root = document.getElementById('dns-sync');
    if (!root) {
        return;
    }
    var urls = JSON.parse(root.dataset.urls);
    var busy = false;
    var stopped = false;

    function zoneBody(id) {
        return root.querySelector('tbody[data-zone-id="' + id + '"]');
    }

    function zoneIds(onlySelected) {
        return Array.from(root.querySelectorAll('tbody[data-zone-id]')).filter(function (body) {
            return !onlySelected || body.querySelector('.dns-sync-select').checked;
        }).map(function (body) {
            return body.dataset.zoneId;
        });
    }

    function updateCounters() {
        var bodies = root.querySelectorAll('tbody[data-zone-id]');
        var counts = {selected: 0, done: 0, error: 0};
        Array.prototype.forEach.call(bodies, function (body) {
            if (body.querySelector('.dns-sync-select').checked) {
                counts.selected++;
            }
            if (counts.hasOwnProperty(body.dataset.status)) {
                counts[body.dataset.status]++;
            }
        });
        Object.keys(counts).forEach(function (key) {
            root.querySelector('[data-counter="' + key + '"]').textContent = counts[key];
        });
    }

    /** Throws on non-2xx or non-JSON answers (e.g. 400 for a missing CSRF token). */
    function post(action, id, params) {
        var body = new URLSearchParams(params || {});
        var url = urls[action] + (urls[action].indexOf('?') === -1 ? '?' : '&') + 'zone=' + encodeURIComponent(id);
        var tbody = zoneBody(id);
        tbody.setAttribute('data-busy', '1');
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                // yii.js adds the CSRF header only to jQuery requests.
                'X-CSRF-Token': yii.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json().catch(function () {
                throw new Error('ответ не JSON');
            });
        }).then(function (json) {
            if (!json || typeof json.row !== 'string') {
                throw new Error('ответ без строки');
            }
            var table = document.createElement('table');
            table.innerHTML = json.row;
            zoneBody(id).replaceWith(table.tBodies[0]);
            updateCounters();
        }).catch(function (error) {
            var current = zoneBody(id);
            current.removeAttribute('data-busy');
            current.querySelector('.dns-sync-request-error').textContent = 'Ошибка запроса: ' + error.message;
            throw error;
        });
    }

    function setBusy(value, total) {
        busy = value;
        root.querySelectorAll('[data-queue], [data-action]').forEach(function (button) {
            button.disabled = value;
        });
        root.querySelector('[data-stop]').classList.toggle('d-none', !value || !total);
        root.querySelector('[data-progress]').classList.toggle('d-none', !total);
    }

    function progress(done, total, text) {
        root.querySelector('[data-progress] .progress-bar').style.width = (total ? 100 * done / total : 0) + '%';
        root.querySelector('[data-progress-text]').textContent = text;
    }

    function runQueue(action, ids) {
        if (busy || !ids.length) {
            return;
        }
        stopped = false;
        setBusy(true, ids.length);
        var index = 0;
        progress(0, ids.length, '0 / ' + ids.length);

        function next() {
            if (stopped || index >= ids.length) {
                setBusy(false, ids.length);
                progress(index, ids.length, index + ' / ' + ids.length + (stopped && index < ids.length ? ' — остановлено' : ''));
                return;
            }
            var id = ids[index];
            post(action, id).then(function () {
                index++;
                progress(index, ids.length, index + ' / ' + ids.length);
                next();
            }, function () {
                // A failed request stops the queue: the next zones are not touched.
                setBusy(false, ids.length);
                progress(index, ids.length, index + ' / ' + ids.length + ' — очередь остановлена из-за ошибки запроса');
            });
        }

        next();
    }

    root.addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button || !root.contains(button)) {
            return;
        }
        if (button.dataset.copy) {
            navigator.clipboard.writeText(button.dataset.copy).then(function () {
                var icon = button.querySelector('.bi');
                icon.className = 'bi bi-clipboard-check';
                setTimeout(function () {
                    icon.className = 'bi bi-clipboard';
                }, 1200);
            });
            return;
        }
        if (button.hasAttribute('data-stop')) {
            stopped = true;
            return;
        }
        if (busy) {
            return;
        }
        if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) {
            return;
        }
        if (button.dataset.queue) {
            runQueue(button.dataset.queue, zoneIds(button.hasAttribute('data-selected-only')));
        } else if (button.dataset.action) {
            var tbody = button.closest('tbody[data-zone-id]');
            var params = button.dataset.params ? JSON.parse(button.dataset.params) : {};
            setBusy(true, 0);
            post(button.dataset.action, tbody.dataset.zoneId, params).then(function () {
                setBusy(false, 0);
            }, function () {
                setBusy(false, 0);
            });
        }
    });

    root.addEventListener('change', function (event) {
        var checkbox = event.target;
        if (!checkbox.classList.contains('dns-sync-select')) {
            return;
        }
        var id = checkbox.closest('tbody[data-zone-id]').dataset.zoneId;
        updateCounters();
        post('toggle', id, {selected: checkbox.checked ? 1 : 0}).catch(function () {
            checkbox.checked = !checkbox.checked;
            updateCounters();
        });
    });

    updateCounters();
}());
