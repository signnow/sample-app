'use strict';

(function () {
  function submitHandler(event) {
    event.preventDefault();

    alertMessage.classList.add('d-none');
    continueButton.classList.add('d-none');
    loadingButton.classList.remove('d-none');
    firstNameInput.setAttribute('disabled', true);
    lastNameInput.setAttribute('disabled', true);
    commentArea.setAttribute('disabled', true);

    fetch(`${window.location.origin}/api/embedded-invites`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        fields: {
          first_name : firstNameInput.value,
          last_name: lastNameInput.value,
          comment: commentArea.value,
        }
      }),
    })
      .then(async response => {
        if (response.ok) {
          return response.json();
        }
        
        return Promise.reject(await response.json());
      })
      .then(({ data: { url }}) => {
        document.body.innerHTML = `
          <iframe
            title="embedded signing"
            class="min-vh-100 w-100 border-0"
            src=${url}
          >
          </iframe>
        `;
      })
      .catch((error) => {
        alertMessage.innerHTML = error?.message || 'Something went wrong.<br>Please try again later.';
        loadingButton.classList.add('d-none');
        continueButton.classList.remove('d-none');
        alertMessage.classList.remove('d-none');
        firstNameInput.removeAttribute('disabled');
        lastNameInput.removeAttribute('disabled');
        commentArea.removeAttribute('disabled');
      });
  }

  form.addEventListener("submit", submitHandler);
})();
