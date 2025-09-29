<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Embedded Editing and Invite - SignNow Sample</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <img src="/img/sign-now.png" alt="Logo">
    </div>

    <!-- Upload Page -->
    <div id="upload-page" class="container-block">
        <h4>Upload Document for Editing and Signing</h4>
        <p>This sample demonstrates uploading a PDF file to SignNow, creating an embedded editing link, and sending invites for signing.</p>
        
        <form id="upload-form" enctype="multipart/form-data">
            <div class="sn-input-group mb-3">
                <label for="document_file">Select PDF Document <span class="text-danger">*</span></label>
                <input type="file" id="document_file" name="document_file" accept=".pdf" required>
                <small class="form-text text-muted">Please select a PDF file to upload</small>
            </div>

            <button type="submit" class="button-primary" id="upload-btn">Upload Document</button>
            <button class="btn btn-dark d-none" id="loading-upload-btn" type="button" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Uploading...
            </button>
        </form>
        
        <div id="upload-result" class="mt-3" style="display: none;">
            <div class="alert alert-success">
                <strong>Success!</strong> Document uploaded successfully. Opening editor...
            </div>
            <div class="text-center">
                <button class="button-primary" id="proceed-to-edit-btn">Open Document Editor</button>
            </div>
        </div>
    </div>


    <!-- Invite Page -->
    <div id="invite-page" class="container-block" style="display: none;">
        <h4>Send Signing Invite</h4>
        <p>Document has been edited successfully. Now you can send an invite to a signer to complete the document.</p>
        
        <div id="recipients-info" class="mb-3" style="display: none;">
            <h5>Document Recipients:</h5>
            <div id="recipients-list"></div>
        </div>
        
        <div id="no-recipients-info" class="mb-3" style="display: none;">
            <div class="alert alert-info">
                <h5>No Recipients Found</h5>
                <p>This document doesn't have any recipients assigned. You need to add recipients before sending invites.</p>
                <button class="button-primary" id="add-recipients-btn">Add Recipients</button>
            </div>
        </div>
        
        <div id="add-recipients-form" class="mb-3" style="display: none;">
            <h5>Add Recipients</h5>
            <form id="recipients-form">
                <div class="sn-input-group mb-3">
                    <label for="recipient_name">Recipient Name <span class="text-danger">*</span></label>
                    <input type="text" id="recipient_name" name="recipient_name" placeholder="Enter recipient name" required>
                </div>
                <div class="sn-input-group mb-3">
                    <label for="recipient_email">Recipient Email <span class="text-danger">*</span></label>
                    <input type="email" id="recipient_email" name="recipient_email" placeholder="Enter recipient email" required>
                </div>
                <div class="sn-input-group mb-3">
                    <label for="recipient_role">Role <span class="text-danger">*</span></label>
                    <select id="recipient_role" name="recipient_role" required>
                        <option value="">Select a role</option>
                        <option value="Signer">Signer</option>
                        <option value="Viewer">Viewer</option>
                    </select>
                </div>
                <div class="text-center">
                    <button type="submit" class="button-primary" id="add-recipient-btn">Add Recipient</button>
                    <button class="btn btn-secondary" id="cancel-add-recipients-btn">Cancel</button>
                    <button class="btn btn-dark d-none" id="loading-add-recipient-btn" type="button" disabled>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Adding recipient...
                    </button>
                </div>
            </form>
        </div>
        
        <div class="text-center">
            <button class="button-primary" id="send-invite-btn">Send Signing Invite</button>
            <button class="btn btn-dark d-none" id="loading-invite-btn" type="button" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Sending invite...
            </button>
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

        async function initUploadPage() {
            const form = document.getElementById('upload-form');
            const uploadBtn = document.getElementById('upload-btn');
            const loadingBtn = document.getElementById('loading-upload-btn');
            const uploadResult = document.getElementById('upload-result');
            const proceedBtn = document.getElementById('proceed-to-edit-btn');
            
            // Check if document_id is in query parameters
            const documentId = getQueryParam('document_id');
            
            if (documentId) {
                // Show success message and proceed button (fallback case)
                uploadResult.style.display = 'block';
                form.style.display = 'none';
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                
                const fileInput = document.getElementById('document_file');

                if (!fileInput.files[0]) {
                    alert('Please select a file');
                    return;
                }

                uploadBtn.classList.add('d-none');
                loadingBtn.classList.remove('d-none');

                const formData = new FormData();
                formData.append('document_file', fileInput.files[0]);
                formData.append('action', 'upload_and_create_dg');

                const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                // Automatically create embedded editor link and redirect
                const editResponse = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_embedded_edit',
                        document_id: result.document_id
                    })
                });

                const editResult = await editResponse.json();
                
                window.location.href = editResult.edit_link;
            });

            proceedBtn.addEventListener('click', async () => {
                const documentId = getQueryParam('document_id');
                
                try {
                    const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create_embedded_edit',
                            document_id: documentId
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        // Redirect to embedded editor
                        window.location.href = result.edit_link;
                    } else {
                        alert('Error: ' + (result.message || 'Failed to create embedded edit link'));
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    console.error(error);
                }
            });
        }


        // Helper function to load document roles
        async function loadDocumentRoles(documentId) {
            try {
                const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get-document-roles',
                        document_id: documentId
                    })
                });

                const result = await response.json();
                
                if (result.success && result.roles && result.roles.length > 0) {
                    const roleSelect = document.getElementById('recipient_role');
                    roleSelect.innerHTML = '<option value="">Select a role</option>';
                    
                    result.roles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.name;
                        option.textContent = role.name;
                        roleSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading document roles:', error);
            }
        }

        async function initInvitePage() {
            const sendInviteBtn = document.getElementById('send-invite-btn');
            const loadingInviteBtn = document.getElementById('loading-invite-btn');
            const recipientsInfo = document.getElementById('recipients-info');
            const recipientsList = document.getElementById('recipients-list');
            const noRecipientsInfo = document.getElementById('no-recipients-info');
            const addRecipientsBtn = document.getElementById('add-recipients-btn');
            const addRecipientsForm = document.getElementById('add-recipients-form');
            const recipientsForm = document.getElementById('recipients-form');
            const addRecipientBtn = document.getElementById('add-recipient-btn');
            const loadingAddRecipientBtn = document.getElementById('loading-add-recipient-btn');
            const cancelAddRecipientsBtn = document.getElementById('cancel-add-recipients-btn');
            const documentId = getQueryParam('document_id');

            if (!documentId) {
                document.getElementById('invite-page').innerHTML = '<p>No Document ID found. Please start over.</p>';
                return;
            }

            // Load and display recipients
            try {
                const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get-recipients',
                        document_id: documentId
                    })
                });

                const result = await response.json();
                
                if (result.success && result.recipients && result.recipients.length > 0) {
                    recipientsList.innerHTML = result.recipients.map(recipient => 
                        `<div class="mb-2">
                            <strong>${recipient.role}</strong> (${recipient.email}) - Order: ${recipient.signing_order}
                            ${recipient.inviter_role ? '<span class="badge bg-primary ms-2">Inviter</span>' : ''}
                        </div>`
                    ).join('');
                    recipientsInfo.style.display = 'block';
                    noRecipientsInfo.style.display = 'none';
                } else {
                    // No recipients found, show add recipients button
                    noRecipientsInfo.style.display = 'block';
                    recipientsInfo.style.display = 'none';
                    
                    // Load available roles for the form
                    await loadDocumentRoles(documentId);
                }
            } catch (error) {
                console.error('Error loading recipients:', error);
            }

            // Add recipients button handler
            addRecipientsBtn.addEventListener('click', () => {
                addRecipientsForm.style.display = 'block';
                noRecipientsInfo.style.display = 'none';
            });

            // Cancel add recipients button handler
            cancelAddRecipientsBtn.addEventListener('click', () => {
                addRecipientsForm.style.display = 'none';
                noRecipientsInfo.style.display = 'block';
            });

            // Add recipient form handler
            recipientsForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                
                const recipientName = document.getElementById('recipient_name').value.trim();
                const recipientEmail = document.getElementById('recipient_email').value.trim();
                const recipientRole = document.getElementById('recipient_role').value;

                if (!recipientName || !recipientEmail || !recipientRole) {
                    alert('Please fill in all required fields');
                    return;
                }

                addRecipientBtn.classList.add('d-none');
                loadingAddRecipientBtn.classList.remove('d-none');

                const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add-recipient',
                            document_id: documentId,
                            recipient_name: recipientName,
                            recipient_email: recipientEmail,
                            recipient_role: recipientRole
                        })
                    });

                    const result = await response.json();
            });

            sendInviteBtn.addEventListener('click', async () => {
                sendInviteBtn.classList.add('d-none');
                loadingInviteBtn.classList.remove('d-none');

                const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_invite',
                        document_id: documentId,
                        signer_name: 'Test Signer',
                        signer_email: 'test@example.com'
                    })
                });

                const result = await response.json();
                
                window.location.href = '/samples/UploadEmbeddedEditingAndInvite?page=status-page&document_id=' + documentId;
            });
        }

        async function initStatusPage() {
            // Notify parent that sample is finished
            parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin);
            
            const documentId = getQueryParam('document_id');
            if (!documentId) {
                document.getElementById('status-page').innerHTML = '<p>No Document ID provided.</p>';
                return;
            }
            
            const documentList = document.getElementById('documentList');
            const noRecipientsContainer = document.getElementById('no-recipients-container');

            // Update statuses and render the list
            async function updateStatuses() {
                try {
                    const response = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'invite-status',
                            document_id: documentId
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
                                const res = await fetch('/api/samples/UploadEmbeddedEditingAndInvite', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        document_id: documentId,
                                        action: 'download-document'
                                    })
                                });

                                if (!res.ok) throw new Error();

                                const blob = await res.blob();
                                const url = window.URL.createObjectURL(blob);
                                Object.assign(document.createElement('a'), { 
                                    href: url, 
                                    download: 'document.pdf' 
                                }).click();
                                window.URL.revokeObjectURL(url);
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
                'upload-page': initUploadPage,
                'invite-page': initInvitePage,
                'status-page': initStatusPage
            }, 'upload-page');
        });
    </script>
</body>
</html>
