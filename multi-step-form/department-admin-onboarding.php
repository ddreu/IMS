<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Department Admin Onboarding</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <div class="container">
        <div id="stepperValue">
            <span class="steps">1</span>
            <span>-</span>
            <span class="steps">2</span>
            <span>-</span>
            <span class="steps">3</span>

        </div>
        <div id="multistep-form-container">

            <!-- Step 1: Welcome Message -->
            <div class="form-container">
                <h2>Welcome to Intramurals Management!</h2>
                <p>Since this is your first login, we will guide you through the setup process step-by-step.</p>
            </div>

            <!-- Step 2: School Admins Handle Setup -->
            <div class="form-container hide">
                <h2>Configuration by School Admins</h2>
                <p>School Administrators are responsible for setting up the games, pointing system, teams, and other configurations needed for your school.</p>
                <p>As a Department Admin, you will manage only the students and teams under your assigned department once setup is complete.</p>
            </div>

            <!-- Step 3: Final Welcome -->
            <div class="form-container hide" style="text-align: center;">
                <img src="../assets/img/5.png" alt="Congratulations" style="max-width: 200px; margin: 0 auto 1.5rem; display: block;">
                <h2>You're All Set!</h2>
                <p>🎉 Congratulations! You are ready to manage your department!</p>
                <p>You can now oversee teams, students, and department-level activities through your dashboard.</p>
            </div>

            <div class="btns">
                <button class="btn hide" id="previous">Previous</button>
                <button class="btn" id="next">Next</button>
                <button class="btn hide" id="submit">Go to Dashboard</button>
            </div>

        </div>

        <p id="error-message"></p>

    </div>

    <!-- Script -->
    <script>
        const formContainer = document.getElementsByClassName("form-container");
        const previousBtn = document.getElementById("previous");
        const nextBtn = document.getElementById("next");
        const submitBtn = document.getElementById("submit");
        const steps = document.getElementsByClassName("steps");

        let currentStep = 0;

        window.onload = () => {
            currentStep = 0;
            steps[currentStep].classList.add("highlight");
            updateStepVisibility(currentStep);
            toggleButtonVisibility();
        };

        const toggleButtonVisibility = () => {
            previousBtn.classList.toggle("hide", currentStep === 0);
            nextBtn.classList.toggle("hide", currentStep === formContainer.length - 1);
            submitBtn.classList.toggle("hide", currentStep !== formContainer.length - 1);
        };

        const updateStepVisibility = (stepIndex) => {
            for (let i = 0; i < formContainer.length; i++) {
                formContainer[i].classList.toggle("hide", i !== stepIndex);
            }
            toggleButtonVisibility();
        };

        const updateStepHighlight = (stepIndex) => {
            for (let i = 0; i < steps.length; i++) {
                steps[i].classList.remove("highlight");
            }
            steps[stepIndex].classList.add("highlight");
        };

        nextBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentStep < formContainer.length - 1) {
                currentStep++;
                updateStepHighlight(currentStep);
                updateStepVisibility(currentStep);
            }
        });

        previousBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentStep > 0) {
                currentStep--;
                updateStepHighlight(currentStep);
                updateStepVisibility(currentStep);
            }
        });

        submitBtn.addEventListener("click", (e) => {
            e.preventDefault();

            fetch('process_dept_admin_setup.php', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = "../department_admin/departmentadmindashboard.php";
                    } else {
                        alert("Something went wrong. Please try again.");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Server error. Please try again later.");
                });
        });
    </script>


</body>

</html>