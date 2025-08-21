<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sample App</title>
</head>
<body>
<div id="download-container">
    <h4>You’ve filled out and signed a document</h4>
    <p>A copy was also sent to your inbox and saved to your signNow account.</p>
    <button type="button" id="download">Download Document</button>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        parent.postMessage({type: 'SAMPLE_APP_FINISHED'}, location.origin);
        const urlParams = new URLSearchParams(window.location.search);
        document.getElementById('download').addEventListener('click', async () => {
            const response = await fetch('/samples/EmbeddedSignerConsentForm', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'document_id=' + encodeURIComponent(urlParams.get('document_id'))
            });
            if (!response.ok) {
                alert('Error downloading the document.');
                return;
            }
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'document.pdf';
            link.click();
            window.URL.revokeObjectURL(url);
        });
    });
</script>
</body>
</html>
