<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EmbeddedSenderWithoutFormFile2</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="/css/styles.css">
    <meta name="description" content="">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#fafafa">
</head>

<body>
<div class="header">
    <img src="/img/sign-now.png" alt="Logo">
</div>


<ul class="status-list" id="documentList"></ul>
<div id="finish-page" class="container-block" style="display: none;">
    <h1>Process Complete</h1>
    <p>The document sending process is complete. You can exit the app or start a new session.</p>
    <button type="button" class="button-primary" id="startNewSession">Start New Session</button>
</div>

<script>
    async function handlePages(pagesMap, defaultKey) {
        const fallbackKey = defaultKey || Object.keys(pagesMap)[0];
        const pageParam = new URLSearchParams(window.location.search).get('page') || fallbackKey;

        Object.keys(pagesMap).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        const handlerKey = pagesMap[pageParam] ? pageParam : fallbackKey;
        const handler = pagesMap[handlerKey];
        const el = document.getElementById(handlerKey);

        if (el) el.style.display = 'block';
        if (typeof handler === 'function') {
            await handler(el);
        } else {
            console.warn(`Handler for "${handlerKey}" not found or not a function.`);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);

        handlePages({
            'download-with-status': async () => {
                parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin);

                // Function for fetching and rendering statuses
                async function updateStatuses() {
                    try {
                        const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                document_id: urlParams.get('document_id'),
                                action: 'invite-status'
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Не удалось получить статусы (invite-status).');
                        }

                        const data = await response.json();
                        const documentList = document.getElementById('documentList');
                        documentList.innerHTML = ''; // clear list

                        if (Array.isArray(data)) {
                            data.forEach(doc => {
                                const li = document.createElement('li');
                                li.className = 'status-item';
                                li.innerHTML = `
                                    <div class="status-wrapper">
                                        <div class="status-info">
                                            <strong>${doc.name}</strong>
                                            <span class="status-date">${doc.timestamp || ''}</span>
                                        </div>
                                    </div>
                                    <div class="status-container">
                                        <div class="status-badge">&#9679; <span>${doc.status}</span></div>
                                    </div>
                                    <div>
                                        <button class="button-outlined download-document">
                                            Download Document
                                        </button>
                                        <button class="button-outlined refresh-status">
                                            Refresh
                                        </button>
                                    </div>
                                `;
                                // Handler for the "Download Document" button
                                const downloadButton = li.querySelector('.download-document');
                                downloadButton.addEventListener('click', async () => {
                                    try {
                                        const response = await fetch('/api/samples/EmbeddedSenderWithoutFormFile', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                document_id: urlParams.get('document_id'),
                                                action: 'download',
                                            })
                                        });

                                        if (!response.ok) {
                                            throw new Error('Failed to download the document.');
                                        }

                                        const blob = await response.blob();
                                        const url = window.URL.createObjectURL(blob);
                                        const link = Object.assign(document.createElement('a'), {
                                            href: url,
                                            download: 'document.pdf'
                                        });
                                        link.click();
                                        window.URL.revokeObjectURL(url);
                                    } catch (error) {
                                        console.error('Error', error);
                                        alert('Error downloading the document.');
                                    }
                                });

                                // Handler for the "Refresh" button
                                const refreshButton = li.querySelector('.refresh-status');
                                refreshButton.addEventListener('click', async () => {
                                    // On click we update the entire list
                                    await updateStatuses();
                                });

                                documentList.appendChild(li);
                            });
                        }
                    } catch (err) {
                        console.error(err);
                        alert('There was an error updating statuses.');
                    }
                }

                updateStatuses();

            }
        });
    });
</script>

</body>

</html>
