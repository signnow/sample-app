<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Embedded Sender - SignNow Sample</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <img src="/img/sign-now.png" alt="Logo">
    </div>

    <!-- Form Page -->
    <div id="form-page" class="container-block">
        <h4>Upload Document for Embedded Sending</h4>
        <p>This sample demonstrates uploading a PDF file to SignNow and creating an embedded sending link.</p>
        
        <div id="upload-content">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Preparing embedded sending interface...</p>
            </div>
        </div>
    </div>

    <!-- Status Page -->
    <div id="status-page" class="container-block" style="display: none;">
        <h4>Document Status & Final Document</h4>
        <p>You can check signing status and download the completed document here.</p>
        
        <div id="no-recipients-container" class="no-recipients" style="display: none;">
            <div class="thank-you-message">
                <img src="/img/no-recipients.svg" alt="signNow" class="mb-3" />
                <h4>No signers found</h4>
                <p class="mb-4">
                    No signers have been added to this document group yet.
                </p>
            </div>
        </div>
        
        <ul class="status-list" id="documentList"></ul>
    </div>

    <script>
        function getQueryParam(key) {
            const params = new URLSearchParams(window.location.search);
            return params.get(key) || '';
        }

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

        async function initFormPage() {
            const uploadContent = document.getElementById('upload-content');

            try {
                const response = await fetch('/api/samples/UploadEmbeddedSender', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'upload_and_create_dg'
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Redirect to embedded sending URL
                    window.location.href = result.embedded_url;
                } else {
                    uploadContent.innerHTML = `
                        <div class="alert alert-danger">
                            Error: ${result.message || 'Failed to upload document and create embedded sending link'}
                        </div>
                    `;
                }
            } catch (error) {
                uploadContent.innerHTML = `
                    <div class="alert alert-danger">
                        Error: ${error.message}
                    </div>
                `;
                console.error(error);
            }
        }

        async function initStatusPage() {
            const documentGroupId = getQueryParam('document_group_id');
            if (!documentGroupId) {
                document.getElementById('status-page').innerHTML = '<p>No Document Group ID provided.</p>';
                return;
            }
            
            const documentList = document.getElementById('documentList');
            const noRecipientsContainer = document.getElementById('no-recipients-container');

            // Update statuses and render the list
            async function updateStatuses() {
                try {
                    const response = await fetch('/api/samples/UploadEmbeddedSender', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'invite-status',
                            document_group_id: documentGroupId
                        })
                    });

                    if (!response.ok) throw new Error('Failed to fetch invite statuses.');

                    const data = await response.json();

                    documentList.innerHTML = '';
                    noRecipientsContainer.style.display = 'none';

                    if (Array.isArray(data) && data.length > 0) {
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
                                    <button class="button-outlined download-document">Download Document</button>
                                    <button class="button-outlined refresh-status">Refresh</button>
                                    <button class="btn btn-dark d-none loading-button" type="button" disabled>
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Refresh
                                    </button>
                                </div>
                            `;

                            li.querySelector('.download-document').addEventListener('click', async () => {
                                try {
                                    const res = await fetch('/api/samples/UploadEmbeddedSender', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            document_group_id: documentGroupId,
                                            action: 'download-doc-group'
                                        })
                                    });

                                    if (!res.ok) throw new Error();

                                    const blob = await res.blob();
                                    const url = window.URL.createObjectURL(blob);
                                    Object.assign(document.createElement('a'), { 
                                        href: url, 
                                        download: 'document_group.pdf' 
                                    }).click();
                                    window.URL.revokeObjectURL(url);
                                    
                                    // Notify parent that sample is finished
                                    parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin);
                                } catch {
                                    alert('Error downloading the document.');
                                }
                            });

                            li.querySelector('.refresh-status').addEventListener('click', async () => {
                                const refreshButton = li.querySelector('.refresh-status');
                                const loadingButton = li.querySelector('.loading-button');

                                refreshButton.classList.add('d-none');
                                loadingButton.classList.remove('d-none');

                                try {
                                    await updateStatuses();
                                } catch (err) {
                                    alert('Failed to refresh status.');
                                } finally {
                                    refreshButton.classList.remove('d-none');
                                    loadingButton.classList.add('d-none');
                                }
                            });

                            documentList.appendChild(li);
                        });
                        return;
                    }

                    noRecipientsContainer.style.display = 'block';
                } catch (err) {
                    console.error(err);
                    alert('Something went wrong while updating statuses.');
                }
            }

            await updateStatuses();
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await handlePages({
                'form-page': initFormPage,
                'status-page': initStatusPage
            }, 'form-page');
        });
    </script>
</body>
</html> 