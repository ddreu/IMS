// Function to handle confirmation dialogs
document.addEventListener("DOMContentLoaded", function () {
  // Select all elements with the class "confirm"
  const confirmElements = document.querySelectorAll(".confirm");

  confirmElements.forEach((element) => {
    element.addEventListener("click", function (event) {
      // Get the confirmation message from the data attribute
      const message = element.getAttribute("data-confirm") || "Are you sure?";

      // Show SweetAlert confirmation dialog
      event.preventDefault(); // Prevent the default action initially
      Swal.fire({
        title: "Confirm Action",
        text: message,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes",
        cancelButtonText: "No",
      }).then((result) => {
        if (result.isConfirmed) {
          // If confirmed, trigger the default action
          element.dispatchEvent(new Event("confirmAction"));
        }
      });
    });

    // Add event listener to re-trigger the default action after confirmation
    element.addEventListener("confirmAction", (e) => {
      if (element.tagName.toLowerCase() === "a") {
        // If it's a link, navigate to the URL
        window.location.href = element.href;
      } else {
        // If it's a form button, submit the form
        const form = element.closest("form");
        if (form) form.submit();
      }
    });
  });
});

// Sidebar toggle functionality without jQuery
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");
  const sidebarToggle = document.getElementById("sidebarCollapse");

  if (sidebar && content && sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("active");
      content.classList.toggle("active");

      // Optionally, toggle the icon
      const icon = sidebarToggle.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-times");
        icon.classList.toggle("fa-align-left");
      }
    });
  }
});
