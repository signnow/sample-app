<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Embedded Editing & Signing with Document Group Template</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<div class="header">
    <img src="/img/sign-now.png" alt="Logo">
</div>

<!-- Page 1: Collect Signer Information -->
<div id="page1-collect-signer-info" class="container-block" style="display: none;">
    <h4>Page 1: Collect Signer Information</h4>
    <form id="signer-info-form">
        <div class="sn-input-group mb-3">
            <label for="signer_1_name">Signer 1 Name <span class="text-danger">*</span></label>
            <input type="text" id="signer_1_name" name="signer_1_name" placeholder="Signer 1 Name" required>
        </div>
        <div class="sn-input-group mb-3">
            <label for="signer_1_email">Signer 1 Email <span class="text-danger">*</span></label>
            <input type="email" id="signer_1_email" name="signer_1_email" placeholder="Signer 1 Email" required>
        </div>
        <div class="sn-input-group mb-3">
            <label for="signer_2_name">Signer 2 Name <span class="text-danger">*</span></label>
            <input type="text" id="signer_2_name" name="signer_2_name" placeholder="Signer 2 Name" required>
        </div>
        <div class="sn-input-group mb-3">
            <label for="signer_2_email">Signer 2 Email</label>
            <input type="email" id="signer_2_email" name="signer_2_email" placeholder="Signer 2 Email">
        </div>
        <div class="divider sn-input-group mt-1 mb-3"></div>

        <button type="submit" class="button-primary" id="submit-signer-form-btn">Submit</button>
        <button class="btn btn-dark d-none" id="loading-signer-form-btn" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Submitting...
        </button>
    </form>
</div>

<!-- Page 2: Embedded Sending Link -->
<div id="page2-embedded-sending" class="container-block" style="display: none;">
    <div class="thank-you-message text-center">
        <h4 id="signing-title" class="mb-3"></h4>
        <p id="signing-instructions" class="mb-3"></p>
        <button class="btn btn-dark d-none" id="loading-signing-btn" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Loading signing session...
        </button>
    </div>
</div>

<!-- Page 3: Create and Access Embedded Invites -->
<div id="page3-create-invite" class="container-block" style="display: none;">
    <h4>Page 3: Create Embedded Invite</h4>
    <p>Provide the "Contract Preparer" email to initiate a signing session.</p>
    <form id="contract-preparer-form">
        <div class="sn-input-group mb-3">
            <label for="contract_preparer_email">Contract Preparer Email</label>
            <input type="email" id="contract_preparer_email" name="contract_preparer_email" placeholder="contract.preparer@example.com" required>
        </div>
        <button type="submit" class="button-primary" id="create-invite-btn">Create Invite & Sign</button>
        <button class="btn btn-dark d-none" id="loading-invite-btn" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Creating invite...
        </button>
    </form>
</div>

<!-- Page 4: Send Status and Document Download -->
<div id="page4-status-download" class="container-block" style="display: none;">
    <h4>Page 4: Send Status & Final Document</h4>
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
    /**
     * Helper: Get URL query param
     */
    function getQueryParam(key) {
        const params = new URLSearchParams(window.location.search);
        return params.get(key) || '';
    }

    /**
     * Show only the page corresponding to "page" param, hide others.
     */
    async function handlePages(pagesMap, defaultPage) {
        const pageParam = getQueryParam('page') || defaultPage;
        const validPages = Object.keys(pagesMap);
        validPages.forEach((p) => {
            const el = document.getElementById(p);
            if (el) el.style.display = 'none';
        });
        const pageToShow = validPages.includes(pageParam) ? pageParam : defaultPage;
        const el = document.getElementById(pageToShow);
        if (el) el.style.display = 'block';

        if (typeof pagesMap[pageToShow] === 'function') {
            await pagesMap[pageToShow](el);
        }
    }

    /**
     * Page 1 logic: Collect Signer Information, submit to create doc group & get edit link
     */
    async function initPage1() {
        const form = document.getElementById('signer-info-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            document.getElementById('submit-signer-form-btn').classList.add('d-none');
            document.getElementById('loading-signer-form-btn').classList.remove('d-none');

            const signer1Name  = document.getElementById('signer_1_name').value.trim();
            const signer1Email = document.getElementById('signer_1_email').value.trim();
            const signer2Name  = document.getElementById('signer_2_name').value.trim();
            const signer2Email = document.getElementById('signer_2_email').value.trim();

            try {
                const response = await fetch('/api/samples/EmbeddedEditingAndSigningDG', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit-signer-info',
                        signer_1_name: signer1Name,
                        signer_1_email: signer1Email,
                        signer_2_name: signer2Name,
                        signer_2_email: signer2Email
                    })
                });
                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();

                // Redirect directly to the embedded editor link
                window.location.href = data.edit_link;
            } catch (error) {
                alert('Error creating Document Group');
                console.error(error);
            } finally {
                document.getElementById('submit-signer-form-btn').classList.remove('d-none');
                document.getElementById('loading-signer-form-btn').classList.add('d-none');
            }
        });
    }

    /**
     * Page 2 logic: Create embedded signing session and redirect to signing link
     */
    async function initPage2() {
        const docGroupId = getQueryParam('document_group_id');
        const loadingBtn = document.getElementById('loading-signing-btn');
        
        if (!docGroupId) {
            alert('Missing Document Group ID');
            return;
        }

        // Show loading state
        loadingBtn.classList.remove('d-none');

        try {
            // Create embedded signing session
            const response = await fetch('/api/samples/EmbeddedEditingAndSigningDG', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create-embedded-invite',
                    document_group_id: docGroupId
                })
            });
            
            if (!response.ok) throw new Error('Failed to create embedded signing session');
            
            const data = await response.json();
            const signingLink = data.signing_link;
            
            if (signingLink) {
                // Redirect to signing link
                window.location.href = signingLink;
            } else {
                throw new Error('No signing link received');
            }
        } catch (error) {
            console.error('Error creating embedded signing session:', error);
            alert('Error creating embedded signing session');
            loadingBtn.classList.add('d-none');
        }
    }

    /**
     * Page 3 logic: Create embedded invite for "Contract Preparer", redirect to sign
     */
    async function initPage3() {
        const form = document.getElementById('contract-preparer-form');
        const docGroupId = getQueryParam('document_group_id');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('contract_preparer_email').value.trim();
            document.getElementById('create-invite-btn').classList.add('d-none');
            document.getElementById('loading-invite-btn').classList.remove('d-none');

            try {
                const response = await fetch('/api/samples/EmbeddedEditingAndSigningDG', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create-embedded-invite',
                        document_group_id: docGroupId,
                        contract_preparer_email: email
                    }),
                });
                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();
                const signingLink = data.signing_link;
                window.location.href = signingLink;
            } catch (error) {
                console.error(error);
                alert('Error creating embedded invite');
            } finally {
                document.getElementById('create-invite-btn').classList.remove('d-none');
                document.getElementById('loading-invite-btn').classList.add('d-none');
            }
        });
    }

    /**
     * Page 4 logic: Show status list with signers and download options
     */
    async function initPage4() {
        const docGroupId = getQueryParam('document_group_id');
        if (!docGroupId) {
            document.getElementById('page4-status-download').innerHTML = '<p>No Document Group ID provided.</p>';
            return;
        }
        
        const documentList = document.getElementById('documentList');
        const noRecipientsContainer = document.getElementById('no-recipients-container');

        // Update statuses and render the list
        async function updateStatuses() {
            try {
                const response = await fetch('/api/samples/EmbeddedEditingAndSigningDG', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'invite-status',
                        document_group_id: docGroupId
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
                                const res = await fetch('/api/samples/EmbeddedEditingAndSigningDG', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        document_group_id: docGroupId,
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
            'page1-collect-signer-info': initPage1,
            'page2-embedded-sending': initPage2,
            'page3-create-invite': initPage3,
            'page4-status-download': initPage4
        }, 'page1-collect-signer-info');
    });
</script>
</body>
</html>