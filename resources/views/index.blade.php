<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sample App Embedded Signing | signNow</title>
        <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}?h={{ build_hash() }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    </head>
    <body>
        <main class="min-vh-100 bg-body-tertiary flex-column py-4 px-3 d-flex justify-content-center align-items-center">
            <h1 class="mb-4 text-center">Welcome to signNow's Embedded Signing sample flow</h1>
            <p class="text-secondary text-center mb-5">This sample flow will demonstrate how embedded signing works with pre-filled data.<br>The data you enter below will be used to fill out the corresponding fields in the sample document.</p>
            <form class="p-4 br-3 bg-white rounded shadow form-container" id="form">
                <div class="form-group mb-3">
                    <label for="firstNameInput">First name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="firstNameInput" required minlength="1">
                </div>
                <div class="form-group mb-3">
                    <label for="lastNameInput">Last name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lastNameInput" required minlength="1">
                </div>
                <div class="form-group mb-3">
                    <label for="commentArea">Comment</label>
                    <textarea class="form-control" id="commentArea" rows="2"></textarea>
                </div>
                <button class="btn btn-primary" id="continueButton" type="submit">Continue</button>
                <button class="btn btn-primary d-none" id="loadingButton" type="submit" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Loading...
                </button>
                <div class="alert alert-danger mt-4 mb-0 d-none overflow-hidden" id="alertMessage" role="alert">
                    Something went wrong.<br>Please try again later.
                </div>
            </form>
            <p class="text-secondary text-center mt-5">Copyright (c) 2024 airSlate, Inc.<br>signNow API Sample Application v{{ version() }}</p>
            <script src="{{ asset('js/main.js') }}?h={{ build_hash() }}"></script>
        </main>
    </body>
</html>
