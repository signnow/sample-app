# signNow Sample App

[![PHP Version](https://img.shields.io/badge/php->=8.2-blue)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-10-cyan)](https://laravel.com/)
[![signNow PHP SDK](https://img.shields.io/badge/signNow_SDK-2.2-light)](https://github.com/signnow/SignNowPHPSDK)
[![Licence](https://img.shields.io/badge/license-MIT-green)](./LICENSE)
## About

This sample application demonstrates how the signNow API and PHP SDK can be used to construct applications with signing flows. This sample application operates using the following features:

* [Authentication](https://docs.signnow.com/docs/signnow/j6jxdlizr86se-generate-access-token)
* [Upload document](https://docs.signnow.com/docs/signnow/i4216w3e1jv3p-upload-document)
* [Add fillable fields](https://docs.signnow.com/docs/signnow/xsttpdx7r60iw-edit-document)
* [Embedded signing](https://docs.signnow.com/docs/signnow/document-embedded-signing/operations/create-a-v-2-document-embedded-invite)

Try using the sample application for testing purposes or use it as a skeleton for your own application. To create an application that generates legally binding signatures, you’ll need a signNow subscription.

## Prerequisites

* Create a signNow account:
   * For integration purposes, you need a signNow account with a paid subscription.
   * For testing purposes, you can [create](https://www.signnow.com/developers) a signNow developer account.
* At the [API Dashboard](https://app.signnow.com/webapp/api-dashboard/keys), create an application.
* Use your signNow credentials and basic authorization token in your config file `.env`.

## Environment variables

The configuration file is located in the project directory with the common name `.env`.

Configure the following variables to ensure the application’s proper functionality.

| Variable           | Example                                    | Description                                                                                    |
|--------------------|--------------------------------------------|------------------------------------------------------------------------------------------------|
| SIGNNOW_API_HOST        | `https://api.signnow.com`                  | signNow API host                                                                               |
| SIGNNOW_API_BASIC_TOKEN | `c2lnbk5vdyBBUEkgc2FtcGxlIEFwcCB2MS4wCg==` | Find your basic token at the [API Dashboard](https://app.signnow.com/webapp/api-dashboard/keys). |
| SIGNNOW_API_USERNAME        | `user@mailer.com`                          | The email address of the document signer.                                |
| SIGNNOW_API_PASSWORD    | `*****`                                    | Your signNow account password.                                                                 |
| SIGNNOW_SIGNER_EMAIL    | `signer@mailer.com`                        | The email address of the person who is supposed to sign a document.                            |

View the entire configuration file, including standard Laravel variables, [here](./.env.example).

## Get Started
1. Clone the repository
   ```
   git clone git@github.com:signnow/sample-app.git
   ```

2. Build a docker image using the following command:
   
   ```
   make build
   ```

3. Start the `signnow-sample-app` docker container using the following command:

   ```
   make up
   ```

4. Install dependencies and generate a Laravel application key using the following command:

   ```
   make setup
   ```
   This command will also prepare your configuration `.env` file and prompt you to enter all the required parameters. Make sure that you have the following data at hand: 
   - Basic token for your application.
   - The email address that your signNow account is registered with.
   - Your signNow password.
   - The email address of the person who is supposed to sign a document.
5. Clear the cache using the following command (optionally):

   ```
   make clear
   ```
6. Open a browser to [localhost:8080](http://localhost:8080).

## Behind the scenes
Technology:
* PHP 8.2
* Laravel 10
* signNow API PHP SDK 2.2
* Docker
* Native JavaScript
* HTML
* Bootstrap CSS Framework

The application contains both backend and tiny frontend components. The frontend consists of a web form where you can enter the first and last names of your signer and a comment.

After the form is submitted, the backend uploads the PDF file to signNow using your credentials provided with the `.env` configuration file. The backend also adds the first and last names and comment fields to the document. The backend then creates an embedded invite to sign your document and generates a link for the invite. This type of invite is called 'embedded' because you can integrate it into your website or mobile application.

The frontend receives the invite link and embeds the signNow document editor into a website or app using an `<iframe/>` tag. Using this method, the signing session is built into the website. You can also customize the signing experience by using the [branding feature](https://docs.signnow.com/docs/signnow/branches/v1.2/guides-branding). All backend operations are performed using the signNow PHP SDK.

## More information

| Topic                 | URL                                        |
|-----------------------|--------------------------------------------|
| signNow website       | <https://www.signnow.com/>                 |
| PHP SDK               | <https://github.com/signnow/SignNowPHPSDK> |
| signNow documentation | <https://docs.signnow.com/>                |

## License

This repository is under the MIT license. You are free to use the code of this application as a skeleton for your application. For more information, see [LICENSE](./LICENSE).
