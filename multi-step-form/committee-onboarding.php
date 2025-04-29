<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Intramurals Setup</title>
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
            <span>-</span>
            <span class="steps">4</span>
            <span>-</span>
            <span class="steps">5</span>
            <span>-</span>
            <span class="steps">6</span>
            <span>-</span>
            <span class="steps">7</span>
        </div>

        <div id="multistep-form-container">

            <!-- Step 1: Welcome Message -->
            <div class="form-container">
                <h2>Welcome to Intramurals Management!</h2>
                <p>Since this is your first login, we will guide you through the setup process step-by-step.</p>
            </div>

            <!-- Step 2: Role of Committee -->
            <div class="form-container hide">
                <h2>Your Role as a Committee Member</h2>
                <p>After the School Admin completes the school's setup, you will manage games under your responsibility.</p>
                <p>You will oversee the teams, players, brackets, and match results of your assigned games.</p>
            </div>

            <!-- Step 3: Review Teams and Players -->
            <div class="form-container hide">
                <h2>Team and Player Management</h2>
                <p>First, review the generated teams and ensure that players are properly registered before proceeding to match setup.</p>
            </div>

            <!-- Step 4: Bracket Creation -->
            <div class="form-container hide">
                <h2>Create Tournament Brackets</h2>
                <p>Once teams are complete, create the bracket. Brackets use random or weighted seeding based on player attributes (like height if required).</p>
            </div>

            <!-- Step 5: Game Configuration -->
            <div class="form-container hide">
                <h2>Configure Your Game</h2>

                <!-- Game Type Dropdown with Proper Style -->
                <div class="form-group">
                    <!-- Game Type Dropdown (Styled to match your existing config) -->
                    <div class="config-field">
                        <label>Select Game Type</label>
                        <select id="game-type" class="input" required>
                            <option value="">Select Game Type</option>
                            <option value="point">Point-Based (Basketball, Soccer, etc.)</option>
                            <option value="set">Set-Based (Volleyball, Tennis, etc.)</option>
                            <option value="default">Default (Board Games, Esports, etc.)</option>
                        </select>
                    </div>

                </div>

                <!-- Game Stats Input styled like Game Badges -->
                <div class="form-group mt-4">
                    <label for="stat-name">Add Game Stat</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" id="stat-name" class="input" placeholder="e.g., Scores, Fouls, Assists" style="flex: 1; height: 42px;">
                        <button type="button" class="btn add-stats" id="add-stat" style="height: 42px; padding: 0 1rem; white-space: nowrap;">Add Stat</button>
                    </div>


                    <!-- Badges Container -->
                    <div id="stat-list" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;"></div>

                </div>



            </div>


            <!-- Step 6: Finalize Matches -->
            <div class="form-container hide">
                <h2>Start Managing Matches</h2>
                <p>Once everything is configured, start running the matches. Notify teams and update the leaderboards automatically!</p>
            </div>

            <!-- Step 7: Congratulations Message -->
            <div class="form-container hide" style="text-align: center;">
                <img src="../assets/img/5.png" alt="Congratulations" style="max-width: 200px; margin: 0 auto 1.5rem; display: block;">
                <h2>You're All Set!</h2>
                <p>🎉 Congratulations! Your game management responsibilities start now.</p>
            </div>

            <div class="btns">
                <button class="btn hide" id="previous">Previous</button>
                <button class="btn" id="next">Next</button>
                <button class="btn hide" id="submit">Submit</button>
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
        const error = document.getElementById("error-message");

        let currentStep = 0;
        let stats = [];

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

        // Handle stat adding
        // Handle stat adding
        document.getElementById('add-stat').addEventListener('click', () => {
            const statInput = document.getElementById('stat-name');
            const statName = statInput.value.trim();

            if (statName !== '') {
                const statList = document.getElementById('stat-list');
                const badge = document.createElement('div');

                badge.className = 'game-badge';
                badge.style.display = 'flex';
                badge.style.alignItems = 'center';
                badge.style.gap = '0.5rem';

                badge.innerHTML = `
            <span>${statName}</span>
            <i class="fas fa-times" style="cursor: pointer;"></i>
        `;

                // Push to stats array
                stats.push(statName);

                // Remove badge and update stats array when clicking 'x'
                badge.querySelector('i').addEventListener('click', (e) => {
                    e.stopPropagation();
                    const index = stats.indexOf(statName);
                    if (index !== -1) {
                        stats.splice(index, 1); // Remove from array
                    }
                    badge.remove(); // Remove the badge from view
                });

                statList.appendChild(badge);
                statInput.value = '';
            }
        });


        // Next button
        nextBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentStep < formContainer.length - 1) {
                currentStep++;
                updateStepHighlight(currentStep);
                updateStepVisibility(currentStep);
            }
        });

        // Previous button
        previousBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentStep > 0) {
                currentStep--;
                updateStepHighlight(currentStep);
                updateStepVisibility(currentStep);
            }
        });

        // Submit button
        submitBtn.addEventListener("click", (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerText = "Submitting...";
            previousBtn.disabled = true;

            const gameType = document.getElementById("game-type").value;

            fetch('process_committee_setup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        game_type: gameType,
                        stats: stats
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        error.innerText = data.message || "An error occurred.";
                        submitBtn.disabled = false;
                        submitBtn.innerText = "Submit";
                        previousBtn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    error.innerText = "An error occurred.";
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Submit";
                    previousBtn.disabled = false;
                });
        });
    </script>

</body>

</html>