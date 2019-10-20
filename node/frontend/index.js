import { html, render } from '../../node_modules/lit-html/lit-html.js';

var data = {
    loading: false,
    settingsVisible: false,
    tasks: [],
    config: { download: null, upload: null },
    elapsed: null,
    version: appVersion,
    fetchInProgress: false,
};

const KB = 1024, MB = KB * 1024, GB = MB * 1024;
const UNREGISTERED_TORRENT_TRACKER_STATUS = 'err: unregistered torrent';

const statusIconMap = {
    'error': 'error.png',
    'finishing': 'wait.png',
    'paused': 'paused.png;',
    'downloading': 'down.png',
    'seeding': 'up.png',
    'waiting': 'wait.png',
    'extracting': 'wait.png',
    'hash_checking': 'wait.png',
    'filehosting_waiting': 'wait.png',
    'finished': 'finished.png'
};

const statusPriorityMap = {
    'error': 9,
    'finishing': 8,
    'paused': 7,
    'downloading': 6,
    'seeding': 5,
    'waiting': 4,
    'extracting': 3,
    'hash_checking': 2,
    'filehosting_waiting': 1,
    'finished': 0
};

const trackerOtherStatusPriority = 2;
const trackerStatusPriorityMap = {
    'Success': 1,
    '': 2,
    'passkey not found': 3,
    'unregistered torrent': 4,
    'Duplicate': 5
};

const trackerStatusIconMap = {
    'Success': 'finished.png',
    '': 'error.png'
};
trackerStatusIconMap[UNREGISTERED_TORRENT_TRACKER_STATUS] = 'error.png';

const friendlySize = (size) => {
    if (size > GB) return `${(size / GB).toFixed(1)} GB`;
    if (size > MB) return `${(size / MB).toFixed(1)} MB`;
    return `${(size / KB).toFixed(1)} KB`;
};
const friendlySpeed = (size) => {
    if (size > MB) return `${(size / MB).toFixed(1)} MB/s`;
    if (size > KB) return `${(size / KB).toFixed(1)} KB/s`;
    return `${size || 0} B/s`;
};

const displaySettings = () => {
    data.loading = true;
    renderPage();
    fetchConfig();
};

const hideSettings = () => {
    data.settingsVisible = false;
    data.loading = false;
    renderPage();
    fetchTasks();
};

const isTaskError = task => task.status === 'error';
const isTaskFinished = task => task.status === 'finished';

const getTaskSizeDown = task => (task.additional && task.additional.transfer && task.additional.transfer.size_downloaded) || 0;
const getTaskSizeUp = task => (task.additional && task.additional.transfer && task.additional.transfer.size_uploaded) || 0;
const getTaskSpeedDown = task => (task.additional && task.additional.transfer && task.additional.transfer.speed_download) || 0;
const getTaskSpeedUp = task => (task.additional && task.additional.transfer && task.additional.transfer.speed_upload) || 0;
const getTaskConnectedSeeds = task => (task.additional && task.additional.detail) ? task.additional.detail.connected_seeders : '';
const getTaskConnectedLeechers = task => (task.additional && task.additional.detail) ? task.additional.detail.connected_leechers : '';
const isTaskConnected = task => (isTaskFinished(task) || (!getTaskConnectedSeeds(task) && !getTaskConnectedLeechers(task))) ? '' :
    `${getTaskConnectedSeeds(task)} / ${getTaskConnectedLeechers(task)}`;
const calcSpeed = task => (isTaskFinished(task) || (!getTaskSpeedDown(task) && !getTaskSpeedUp(task))) ? '' : `${friendlySpeed(getTaskSpeedDown(task))} / ${friendlySpeed(getTaskSpeedUp(task))}`;
const calcProgress = task => isTaskError(task) ? '' :
    (task.size > 0 ? (getTaskSizeDown(task) === task.size ? '' :
        `${Math.round(getTaskSizeDown(task) / task.size * 100).toFixed(1)} %`) : '0 %');
const calcRatio = task => `${(getTaskSizeUp(task) / (getTaskSizeDown(task) || 1)).toFixed(2)}`;

const transformTask = task => {
    return {
        id: task.id,
        title: task.title,
        friendlySize: friendlySize(task.size),
        sizeDown: friendlySize(getTaskSizeDown(task)),
        sizeUp: friendlySize(getTaskSizeUp(task)),
        progress: calcProgress(task),
        ratio: calcRatio(task),
        speed: calcSpeed(task),
        speedDown: getTaskSpeedDown(task),
        speedUp: getTaskSpeedUp(task),
        trackerStatus: getTrackerStatus(task),
        trackerStatuses: getTrackerStatuses(task),
        taskStatus: task.status,
        connected: isTaskConnected(task),
        statusCssClass_: ''
    };
};

const calcTotalSpeedDown = tasks => tasks.reduce((prev, task) => prev + task.speedDown, 0);
const calcTotalSpeedUp = tasks => tasks.reduce((prev, task) => prev + task.speedUp, 0);

const calcTotalSpeed = () => {
    const totalDown = calcTotalSpeedDown(data.tasks);
    const totalUp = calcTotalSpeedUp(data.tasks);

    if (totalDown || totalUp) {
        return `${friendlySpeed(totalDown)} / ${friendlySpeed(totalUp)}`;
    }

    return '';
};

const getTaskStatusExtra = task => task.status_extra ? (task.status_extra.error_detail === 'torrent_duplicate' ? 'Duplicate' : task.status_extra.error_detail) : '';

const getTrackerStatuses = (task) => {
    if (!task.additional || !task.additional.tracker) {
        return [getTaskStatusExtra(task) || 'Success'];
    }
    if (!Array.isArray(task.additional.tracker)) {
        task.additional.tracker = [task.additional.tracker];
    }

    const statuses = task.additional.tracker.map(t => t.status);
    return [...new Set(statuses)];
}

const getTrackerStatus = (task) => {
    const statuses = getTrackerStatuses(task);
    const unreg = statuses.find(s => s === UNREGISTERED_TORRENT_TRACKER_STATUS);
    if (unreg) return unreg;
    const success = statuses.find(s => s === 'Success');
    if (success) return success;
    return statuses[0];
};

const compareByName = (a, b) => {
    const at = a.title.toUpperCase(), bt = b.title.toUpperCase();
    if (at < bt) return -1;
    if (at > bt) return 1;
    return 0;
};
const compareNumbers = (a, b) => a < b ? 1 : (a > b ? -1 : 0);

const sortTasks = (tasks) => {
    return tasks.sort((a, b) => {
        // 1: trackerstatus
        // 2: status
        // 3: name

        const aTrPrio = trackerStatusPriorityMap[a.trackerStatus] || trackerOtherStatusPriority;
        const bTrPrio = trackerStatusPriorityMap[b.trackerStatus] || trackerOtherStatusPriority;
        const aStPrio = statusPriorityMap[a.taskStatus];
        const bStPrio = statusPriorityMap[b.taskStatus];

        if (a.trackerStatus === b.trackerStatus) {
            if (a.taskStatus === b.taskStatus) {
                return compareByName(a, b);
            }

            return compareNumbers(aStPrio, bStPrio);
        }

        if (aTrPrio === bTrPrio) {
            if (a.taskStatus === b.taskStatus) {
                return compareByName(a, b);
            }

            return compareNumbers(aStPrio, bStPrio);
        }

        return compareNumbers(aTrPrio, bTrPrio);
    });
};

const calcElapsedTime = started => ((Date.now() - started) / 1000).toFixed(1);

const fetchConfig = () => {
    try {
        fetch('/config')
            .then(configResponse => {
                if (!configResponse.ok) {
                    throw Error(configResponse.statusText);
                }
                return configResponse.json();
            })
            .then(configResponse => {
                data.config = configResponse;
                data.settingsVisible = true;
                data.loading = false;
                renderPage();
            })
            .catch(err => {
                renderError(err);
            });
    }
    catch (err) {
        data.settingsVisible = true;
        data.loading = false;
        renderError(err);
    }
};

const saveConfig = () => {
    try {
        data.savingConfig = true;
        renderPage();

        fetch('/config', {
            method: 'POST',
            body: JSON.stringify(data.config)
        })
            .then(resp => {
                if (!resp.ok) {
                    throw Error(resp.statusText);
                }

                data.settingsVisible = false;
                data.loading = false;
                data.savingConfig = false;
                renderPage();
            })
            .catch(err => {
                data.savingConfig = false;
                renderError(err);
            })
            .finally(() => {
                data.settingsVisible = false;
                data.loading = false;
                data.savingConfig = false;
            });
    }
    catch (err) {
        data.settingsVisible = false;
        data.loading = false;
        data.savingConfig = false;
        renderError(err);
    }
};

const fetchTasks = () => {
    if (data.fetchInProgress) {
        return;
    }
    data.fetchInProgress = true;
    const started = Date.now();
    try {
        fetch('/tasks')
            .then(tasksResponse => {
                if (!tasksResponse.ok) {
                    throw Error(tasksResponse.statusText);
                }
                return tasksResponse.json();
            })
            .then(tasksResponse => sortTasks(tasksResponse.map(transformTask)))
            .then(transformedTasks => {
                data.tasks = transformedTasks;
                data.elapsed = calcElapsedTime(started);
                data.fetchInProgress = false;
                renderPage();
            })
            .catch(err => {
                renderError(err);
            });
    }
    catch (e) {
        data.fetchInProgress = false;
        renderError(err);
    }
};

// TODO: restrict form input field with regex
const downloadChanged = (event) => data.config.download = event.target.value ? Math.abs(Number.parseInt(event.target.value)) : 0;
const uploadChanged = (event) => data.config.upload = event.target.value ? Math.abs(Number.parseInt(event.target.value)) : 0;

const loadingTemplate = () => html`<center>Loading...</center>`;
const settingsTemplate = () => html`
<div class="config">
    <form method="post" class="form-group">
        <label>Donwload limit</label>
        <input type="number" value="${data.config.download}" @change=${downloadChanged}> 
        <br />
        <label>Upload limit</label>
        <input type="number" value="${data.config.upload}" @change=${uploadChanged}>
        <br />
    </form>
    <div>
        <span class="button" @click=${saveConfig}>Save</span>
        <span class="button" @click=${hideSettings}>Close</span>
    </div>
</div>`;

const rowTemplate = (task) => html`
<article id='task${task.id}'>
    <span class='title large medium small'>${task.title}</span>
    <span class='size large medium'>${task.friendlySize}</span>
    <span class='transfer large medium'><span>${task.sizeDown}</span>/<br><span>${task.sizeUp}</span><br><span>${task.progress}</span></span>
    <span class='ratio large medium small'>${task.ratio}</span>
    <span class='speed large medium'>${task.speed}</span>
    <span class='tracker large medium small'><img src="${trackerStatusIconMap[task.trackerStatus] || 'wait.png'}" alt="${task.trackerStatus}" title="${task.trackerStatuses.join(', ')}" /></span>
    <span class='connected large medium'>${task.connected}</span>
    <span class='status large medium small'><img src="${statusIconMap[task.taskStatus]}" alt="${task.taskStatus}" /></span>
</article>`;

const tasksTemplate = () => html`
<article class='header'>
    <span class='title large medium small'>Name</span>
    <span class='size large medium'>Size</span>
    <span class='transfer large medium'>Transfer</span>
    <span class='ratio large medium small'>Ratio</span>
    <span class='speed large medium'>Speed</span>
    <span class='tracker large medium'>Tracker</span>
    <span class='tracker small'>Tr.</span>
    <span class='connected large'>Seed/Leech</span>
    <span class='connected medium'>S/L</span>
    <span class='status large medium'>Status</span>
    <span class='status small'>St</span>
</article>
${data.tasks.map(rowTemplate)}
<article class="footer">
    <span class='title large medium small'>Total: ${data.tasks.length}</span>
    <span class='size large medium'>&nbsp;</span>
    <span class='transfer large medium'>&nbsp;</span>
    <span class='ratio large medium small'>&nbsp;</span>
    <span class='speed large medium'>${calcTotalSpeed()}</span>
    <span class='tracker large medium small'>&nbsp;</span>
    <span class='connected large medium'>&nbsp;</span>
    <span class='status large medium small'>&nbsp;</span>
</article>`;

const pageTemplate = () => html`
<header>
    <div class='title'>Download Station Monitor ${data.version}</div>
    <div class='button btn btn-outline-info' @click=${displaySettings}>Settings</div>
</header>
<main class="${(data.savingConfig || data.loading) ? 'wait' : ''}">${
    (data.loading || ((data.tasks || []).length === 0)) ?
        loadingTemplate() :
        (data.settingsVisible ? settingsTemplate() : tasksTemplate())
    }</main>`;

const errorTemplate = (error) => html`
<header>
    <div class='title'>Download Station Monitor ${data.version}</div>
    <div class='button btn btn-outline-info' @click=${displaySettings}>Settings</div>
</header>
<main><pre>${error}</pre></main>`;

var renderPage = () => {
    render(pageTemplate(), document.body);
};

var renderError = (error) => {
    render(errorTemplate(error), document.body);
};

setInterval(() => fetchTasks(), uiReload || 60000);
fetchTasks();
renderPage();