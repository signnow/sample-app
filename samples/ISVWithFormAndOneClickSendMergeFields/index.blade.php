<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISV with Form and One Click Send - Merge Fields - SignNow Sample</title>
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
        <h4>Enter Customer Information</h4>
        <form id="customer-form">
            <div class="sn-input-group mb-3">
                <label for="customer_name">Customer Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="company_name">Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="company_name" name="company_name" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="button-primary" id="submit-btn">
                Send Documents
            </button>
            <button type="button" class="btn btn-dark d-none" id="loading-btn" disabled>
                <span class="spinner-border spinner-border-sm me-2"></span>
                Processing...
            </button>
        </form>
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
            const form = document.getElementById('customer-form');
            const submitBtn = document.getElementById('submit-btn');
            const loadingBtn = document.getElementById('loading-btn');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = {
                    action: 'prepare_dg',
                    customer_name: formData.get('customer_name'),
                    company_name: formData.get('company_name'),
                    email: formData.get('email')
                };

                submitBtn.classList.add('d-none');
                loadingBtn.classList.remove('d-none');

                try {
                    const response = await fetch('/api/samples/ISVWithFormAndOneClickSendMergeFields', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect directly to status page
                        window.location.href = `?page=status-page&document_group_id=${result.document_group_id}`;
                    } else {
                        alert(result.message || 'An error occurred');
                    }
                } catch (error) {
                    alert('An error occurred while processing your request');
                    console.error(error);
                } finally {
                    loadingBtn.classList.add('d-none');
                    submitBtn.classList.remove('d-none');
                }
            });
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
                    const response = await fetch('/api/samples/ISVWithFormAndOneClickSendMergeFields', {
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
                                    const res = await fetch('/api/samples/ISVWithFormAndOneClickSendMergeFields', {
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