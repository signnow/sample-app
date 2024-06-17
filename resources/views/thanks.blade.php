<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sample App Embedded Signing | signNow</title>
        <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}?h={{build_hash()}}">
        <link rel="icon" type="image/x-icon" href="{{ secure_asset('favicon.ico') }}">
    </head>
    <body>
        <main class="d-flex min-vh-100 text-center py-4 px-3 justify-content-center align-items-center bg-body-tertiary">
            <div>
                <h1 class="mb-4">Thank you for completing the signNow<br>Embedded Signing flow</h1>
                <p class="text-secondary mb-6">You’ve completed our sample flow. Ready to get started with signNow API?<br>Build your own application and integrate it with your system or existing workflows.</p>
                <p class="mb-0 fw-bold fs-5-1">Sign up for free and start testing our API</p>
                <p class="text-secondary fs-7">Seamlessly integrate and deploy your apps from development to production in one click.</p>
                <a class="btn btn-primary mb-8" href="https://www.signnow.com/developers" target="_blank" role="button">Create a Free signNow Developer Account</a>
                <p class="text-secondary">Check the links below for more information</p>
                <a class="mb-2 me-2 text-nowrap" href="https://github.com/signnow/sample-app" target="_blank" role="button">Download Sample App from GitHub</a>
                |
                <a class="ms-2 mb-2 text-nowrap" href="https://docs.signnow.com/docs/signnow/sample-apps" target="_blank" role="button">API Documentation</a>
            </div>
        </main>
    </body>
</html>
