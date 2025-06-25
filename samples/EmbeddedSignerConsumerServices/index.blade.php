<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veterinary Clinic Intake Form</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="/css/styles.css">
    <meta name="description" content="">

    <meta property="og:title" content="Veterinary Clinic Intake Form">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">
    <meta property="og:image:alt" content="">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#fafafa">
</head>

<body>

<div class="header">
    <img src="/img/sign-now.png" alt="Logo">
</div>

<div id="download-container">
    <div class="thank-you-message">
        <img src="/img/doc-completed.png" alt="signNow" class="mb-3" />
        <h4>You’ve filled out and signed a document</h4>
        <p class="mb-4">A copy was also sent to your inbox and saved to your signNow account.</p>
        <button type="button" class="button-secondary">Download Document</button>
    </div>
</div>

<div class="copyright gray--700 mt-3">Copyright (c) 2025 airSlate, Inc., SignNow API Sample Application v3.0</div>

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
        handlePages({
            'download-container' : async (el) => {
                parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin)
                const downloadButton = document.querySelector('.button-secondary');

                downloadButton.addEventListener('click', async () => {
                    try {
                        const response = await fetch('/api/samples/EmbeddedSignerConsumerServices', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                document_id: new URLSearchParams(window.location.search).get('document_id')
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Error fetching the file');
                        }

                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);

                        const link = Object.assign(document.createElement('a'), {
                            href: url,
                            download: 'completed_document.pdf'
                        });
                        link.click();

                        window.URL.revokeObjectURL(url);
                    } catch (error) {
                        console.error('Error downloading document', error);
                        alert('Failed to download the document.');
                    }
                });
            }
        }, 'download-container')
    });
</script>

</body>

</html>
