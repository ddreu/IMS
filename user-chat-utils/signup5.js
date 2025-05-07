const form = document.querySelector(".signup form"),
  continueBtn = form.querySelector(".button input"),
  errorText = form.querySelector(".error-text");

form.onsubmit = (e) => {
  e.preventDefault();
};

continueBtn.onclick = () => {
  continueBtn.setAttribute("disabled", "true");
  continueBtn.value = "Connecting...";

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "../user-chat-utils/signup.php", true);
  xhr.onload = () => {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        let data = xhr.response.trim();
        if (data === "success") {

          //set a flag if successful to reopen the wrapper of chat
          localStorage.setItem('chatBoxVisible', 'true');

          // reload
          window.location.reload();
        } else {
          continueBtn.removeAttribute("disabled");
          continueBtn.value = "Connect to Support";
          errorText.style.display = "block";
          errorText.textContent = data;
        }
      }
    }
  };

  let formData = new FormData(form);
  xhr.send(formData);
};
