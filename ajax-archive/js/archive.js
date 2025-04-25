// FILTER

// document.addEventListener("DOMContentLoaded", () => {
//   const filterButtons = document.querySelectorAll(".filter-btn");
//   const tableBody = document.querySelector("tbody");

//   filterButtons.forEach((button) => {
//     button.addEventListener("click", () => {
//       filterButtons.forEach((btn) => btn.classList.remove("active"));
//       button.classList.add("active");

//       const category = button.getAttribute("data-category");

//       // Use event delegation on tbody
//       tableBody.querySelectorAll("tr").forEach((row) => {
//         if (
//           category === "all" ||
//           row.getAttribute("data-category") === category
//         ) {
//           row.style.display = "";
//         } else {
//           row.style.display = "none";
//         }
//       });
//     });
//   });

//   // Trigger default filter
//   document.querySelector('.filter-btn[data-category="0"]').click();
// });

document.addEventListener("DOMContentLoaded", () => {
  const filterButtons = document.querySelectorAll(".filter-btn");
  const tableRows = document.querySelectorAll("tbody tr");

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      const category = button.getAttribute("data-category");

      tableRows.forEach((row) => {
        if (
          category === "all" ||
          row.getAttribute("data-category") === category
        ) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      });
    });
  });

  document.querySelector('.filter-btn[data-category="0"]').click();
});

/* ARCHIVE */

document.addEventListener("click", function (event) {
  if (event.target.classList.contains("archive-btn")) {
    const button = event.target;
    const id = button.getAttribute("data-id");
    const table = button.getAttribute("data-table");
    const operation = button.getAttribute("data-operation"); // New: archive or unarchive

    if (!id || !table || !operation) return;

    const isArchive = operation === "archive";

    // SweetAlert confirmation
    Swal.fire({
      title: isArchive
        ? "Are you sure you want to archive this?"
        : "Are you sure you want to unarchive this?",
      text: `You are about to ${
        isArchive ? "archive" : "unarchive"
      } this record from ${table}.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: isArchive ? "#3085d6" : "#28a745",
      cancelButtonColor: "#d33",
      confirmButtonText: isArchive ? "Yes, archive it!" : "Yes, unarchive it!",
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request
        fetch("../archive/archive_handler.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            id: id,
            action: table,
            operation: operation, // Include operation
          }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: isArchive ? "Archived!" : "Unarchived!",
                text: `The record has been ${
                  isArchive ? "archived" : "unarchived"
                } successfully.`,
                icon: "success",
                toast: true,
                position: "top-end",
                timer: 2000,
                showConfirmButton: false,
              });

              // OPTIONAL: Reload or update the table
              setTimeout(() => {
                location.reload();
              }, 1200);
            } else {
              Swal.fire({
                title: "Error!",
                text:
                  data.message ||
                  `Failed to ${
                    isArchive ? "archive" : "unarchive"
                  } the record.`,
                icon: "error",
              });
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
              title: "Error!",
              text: `An error occurred while trying to ${
                isArchive ? "archive" : "unarchive"
              } the record.`,
              icon: "error",
            });
          });
      }
    });
  }
});

/* ARCHIVE SCHOOL DATA */

document.addEventListener("DOMContentLoaded", () => {
  const archiveForm = document.getElementById("archiveSchoolForm");

  archiveForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // Show confirmation alert
    Swal.fire({
      title: "Are you sure?",
      text: "You want to archive this school and its data?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, archive it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading alert
        Swal.fire({
          title: "Archiving...",
          text: "Please wait while the school is being archived.",
          icon: "info",
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          },
        });

        // Prepare form data
        const formData = new FormData(archiveForm);

        // Send AJAX request
        fetch("../archive/archive_school.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            Swal.close(); // Close loading alert
            if (data.success) {
              /*
              // Create and trigger download of Excel file
              const downloadExcel = () => {
                const link = document.createElement('a');
                link.href = data.excel_url;
                link.download = data.excel_url.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
              };
              */

              Swal.fire({
                title: "Archived!",
                text: data.message,
                icon: "success",
                confirmButtonColor: "#198754",
                showDenyButton: true,
                // confirmButtonText: "Download Report",
                denyButtonText: "Close",
              }).then((result) => {
                /*
                if (result.isConfirmed) {
                  downloadExcel();
                }
                */
                // Reload the page after download or close
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Error!",
                text: data.message,
                icon: "error",
                confirmButtonColor: "#dc3545",
              });
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
              title: "Error!",
              text: "Something went wrong. Please try again.",
              icon: "error",
              confirmButtonColor: "#dc3545",
            });
          });
      }
    });
  });
});

/* UNARCHIVE SCHOOL DATA */


document.addEventListener("DOMContentLoaded", () => {
  const unarchiveForm = document.getElementById("UnarchiveSchoolForm");

  unarchiveForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // 🔄 Fetch available archived years first
    const yearResponse = await fetch("../archive/get_archive_years.php");
    const yearData = await yearResponse.json();

    if (!yearData.success || !Array.isArray(yearData.years) || yearData.years.length === 0) {
      Swal.fire({
        title: "No Archived Data",
        text: "No archived data found for this school.",
        icon: "info",
        confirmButtonColor: "#6c757d",
      });
      return;
    }

    // 🔽 Build dropdown options
    const yearOptions = yearData.years.map((year) => `<option value="${year}">${year}</option>`).join("");

    // 🔔 Ask user to pick a year to unarchive
    Swal.fire({
      title: "Select Year to Unarchive",
      html: `
        <p>Please select the year to restore data from:</p>
        <select id="archiveYear" class="swal2-input" required>
          <option value="">--Select Year--</option>
          ${yearOptions}
        </select>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#198754",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Unarchive",
      cancelButtonText: "Cancel",
      preConfirm: () => {
        const selectedYear = document.getElementById("archiveYear").value;
        if (!selectedYear) {
          Swal.showValidationMessage("Please select a year.");
        }
        return selectedYear;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const selectedYear = result.value;

        // ⏳ Show loading alert
        Swal.fire({
          title: "Unarchiving...",
          text: "Please wait while the school is being restored.",
          icon: "info",
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          },
        });

        // 📨 Prepare form data with year
        const formData = new FormData(unarchiveForm);
        formData.append("year", selectedYear);

        // 🔁 Send AJAX request to PHP backend
        fetch("../archive/unarchiver.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: "Unarchived!",
                text: data.message,
                icon: "success",
                confirmButtonColor: "#198754",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Error!",
                text: data.message,
                icon: "error",
                confirmButtonColor: "#dc3545",
              });
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
              title: "Error!",
              text: "Something went wrong. Please try again.",
              icon: "error",
              confirmButtonColor: "#dc3545",
            });
          });
      }
    });
  });
});

// document.addEventListener("DOMContentLoaded", () => {
//   const unarchiveForm = document.getElementById("UnarchiveSchoolForm");

//   unarchiveForm.addEventListener("submit", (e) => {
//     e.preventDefault();

//     // Show confirmation alert
//     Swal.fire({
//       title: "Are you sure?",
//       text: "You want to unarchive this school's data?",
//       icon: "warning",
//       showCancelButton: true,
//       confirmButtonColor: "#198754",
//       cancelButtonColor: "#6c757d",
//       confirmButtonText: "Yes, unarchive it!",
//       cancelButtonText: "Cancel",
//       reverseButtons: true,
//     }).then((result) => {
//       if (result.isConfirmed) {
//         // Show loading alert
//         Swal.fire({
//           title: "Unarchiving...",
//           text: "Please wait while the school is being restored.",
//           icon: "info",
//           allowOutsideClick: false,
//           showConfirmButton: false,
//           willOpen: () => {
//             Swal.showLoading();
//           },
//         });

//         // Prepare form data
//         const formData = new FormData(unarchiveForm);

//         // Send AJAX request
//         fetch("../archive/unarchiver.php", {
//           method: "POST",
//           body: formData,
//         })
//           .then((response) => response.json())
//           .then((data) => {
//             Swal.close();
//             if (data.success) {
//               Swal.fire({
//                 title: "Unarchived!",
//                 text: data.message,
//                 icon: "success",
//                 confirmButtonColor: "#198754",
//               }).then(() => {
//                 location.reload(); // Reload after successful unarchive
//               });
//             } else {
//               Swal.fire({
//                 title: "Error!",
//                 text: data.message,
//                 icon: "error",
//                 confirmButtonColor: "#dc3545",
//               });
//             }
//           })
//           .catch((error) => {
//             console.error("Error:", error);
//             Swal.fire({
//               title: "Error!",
//               text: "Something went wrong. Please try again.",
//               icon: "error",
//               confirmButtonColor: "#dc3545",
//             });
//           });
//       }
//     });
//   });
// });

/* UNARCHIVE */

// document.addEventListener("click", function (event) {
//   if (event.target.classList.contains("unarchive-btn")) {
//     const id = event.target.dataset.id;
//     const action = event.target.dataset.action; // Example: 'announcements' or 'events'

//     Swal.fire({
//       title: "Are you sure?",
//       text: "This will unarchive the item.",
//       icon: "warning",
//       showCancelButton: true,
//       confirmButtonText: "Yes, unarchive it!",
//       cancelButtonText: "Cancel",
//     }).then((result) => {
//       if (result.isConfirmed) {
//         fetch("archive_handler.php", {
//           method: "POST",
//           headers: {
//             "Content-Type": "application/x-www-form-urlencoded",
//           },
//           body: `id=${id}&action=unarchive&table=${action}`,
//         })
//           .then((response) => response.json())
//           .then((data) => {
//             if (data.success) {
//               Swal.fire(
//                 "Unarchived!",
//                 "The item has been unarchived.",
//                 "success"
//               );
//             } else {
//               Swal.fire("Error!", data.message, "error");
//             }
//           });
//       }
//     });
//   }
// });
