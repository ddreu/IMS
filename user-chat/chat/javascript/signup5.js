const form = document.querySelector(".signup form"),
  continueBtn = form.querySelector(".button input"),
  errorText = form.querySelector(".error-text");

form.onsubmit = (e) => {
  e.preventDefault();
};
continueBtn.onclick = () => {
  continueBtn.setAttribute("disabled", "true");
  continueBtn.value = "Processing";
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "includes/chat/php/signup.php", true);
  xhr.onload = () => {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        let data = xhr.response;
        if (data === "success") {
          window.location.reload();
        } else {
          continueBtn.removeAttribute("disabled", "false");
          continueBtn.value = "Try Again";
          errorText.style.display = "block";
          errorText.textContent = data;
        }
      }
    }
  };
  let formData = new FormData(form);
  xhr.send(formData);
};
