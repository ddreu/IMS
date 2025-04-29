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

            <!-- STEP 2: Select Games -->
            <!-- Updated STEP 2: Select Games with Badges -->
            <div class="form-container hide">
                <h2>Select Games Played in Your School</h2>
                <div id="games-checkboxes">
                    <label class="game-badge">
                        <input type="checkbox" value="Basketball">
                        <i class="fas fa-basketball-ball"></i> Basketball
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Volleyball">
                        <i class="fas fa-volleyball-ball"></i> Volleyball
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Badminton">
                        <i class="fas fa-shuttlecock"></i> Badminton
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Table Tennis">
                        <i class="fas fa-table-tennis"></i> Table Tennis
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Football">
                        <i class="fas fa-futbol"></i> Football
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Chess">
                        <i class="fas fa-chess"></i> Chess
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Soccer">
                        <i class="fas fa-futbol"></i> Soccer
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Swimming">
                        <i class="fas fa-swimmer"></i> Swimming
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Track and Field">
                        <i class="fas fa-running"></i> Track & Field
                    </label>
                    <label class="game-badge">
                        <input type="checkbox" value="Others" id="others-checkbox">
                        <i class="fas fa-plus"></i> Others
                    </label>
                </div>
                <input type="text" class="input hide" id="other-game-input" placeholder="Enter custom game name" />
            </div>

            <!-- STEP 3: Configure Selected Games -->
            <div class="form-container hide">
                <h2>Configure Your Games</h2>
                <div id="games-configuration-list"></div>
            </div>

            <!-- STEP 4: Pointing System -->
            <div class="form-container hide">
                <h2>Understanding the Pointing System</h2>
                <p>The system will automatically award points based on the values you set below:</p>
                <div id="points-setup">
                    <label for="first-place">First Place Points</label>
                    <input type="number" class="input" id="first-place" placeholder="e.g., 15">

                    <label for="second-place">Second Place Points</label>
                    <input type="number" class="input" id="second-place" placeholder="e.g., 10">

                    <label for="third-place">Third Place Points</label>
                    <input type="number" class="input" id="third-place" placeholder="e.g., 5">
                </div>
            </div>




            <!-- Step 5: Departments and Teams -->
            <div class="form-container hide">
                <h2>Departments and Team Generation</h2>
                <p>Adding courses to College, JHS, SHS, and Elementary departments will automatically create teams for every registered game.</p>
            </div>

            <!-- Step 6: Setup Admins and Committees -->
            <div class="form-container hide">
                <h2>Setup Admins and Committees</h2>
                <p>After this setup, you will add department administrators and committee members for better management.</p>
            </div>

            <!-- Step 7: Congratulations Message -->
            <div class="form-container hide" style="text-align: center;">
                <img src="../assets/img/5.png" alt="Congratulations" style="max-width: 200px; margin: 0 auto 1.5rem; display: block;">
                <h2>You're All Set!</h2>
                <p>🎉 Congratulations! Your school is now ready and set up.</p>
                <p>You can now begin managing games, departments, and events efficiently.</p>
                <p style="margin-top: 1rem;">If you'd like to review your setup, you can go back using the <strong>Previous</strong> button.</p>
                <p>Don't worry — you can always update this information later in your dashboard.</p>
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

        const othersCheckbox = document.getElementById('others-checkbox');
        const otherGameInput = document.getElementById('other-game-input');
        const gamesContainer = document.getElementById('games-checkboxes');

        let currentStep = 0;

        window.onload = () => {
            currentStep = 0;
            steps[currentStep].classList.add("highlight");
            updateStepVisibility(currentStep);
            toggleButtonVisibility();
            setupBadgeClicks();
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

        let formData = {
            games: [],
            gameConfigs: [],
            points: {
                first: null,
                second: null,
                third: null
            }
        };


        // Validate current step before proceeding
        function validateStep(step) {
            error.innerText = '';

            if (step === 1) {
                const selectedGames = document.querySelectorAll('#games-checkboxes input[type="checkbox"]:checked');
                if (selectedGames.length === 0) {
                    error.innerText = 'Please select at least one game.';
                    return false;
                }

                formData.games = Array.from(selectedGames).map(cb => cb.value);

                // Also build the game config form
                const configContainer = document.getElementById('games-configuration-list');
                configContainer.innerHTML = '';

                formData.games.forEach(game => {
                    const div = document.createElement('div');
                    div.className = 'game-config-item';
                    div.innerHTML = `
    <h4>${game}</h4>
    <div class="game-config-row">
        <div class="config-field">
            <label>Number of Players per Team</label>
<input type="number" class="input" name="players_${game}" required min="1">
        </div>
        <div class="config-field">
            <label>Category</label>
            <select name="category_${game}" class="input" required>
                <option value="">Select Category</option>
                <option value="Team Sports">Team Sports</option>
                <option value="Individual Sports">Individual Sports</option>
                <option value="Dual Sports">Dual Sports</option>
            </select>
        </div>
        <div class="config-field">
            <label>Environment</label>
            <select name="environment_${game}" class="input" required>
                <option value="">Select Environment</option>
                <option value="Indoor">Indoor</option>
                <option value="Outdoor">Outdoor</option>
            </select>
        </div>
    </div>
    <hr>
`;

                    configContainer.appendChild(div);
                });
            }

            if (step === 2) {
                // Collect game configs
                formData.gameConfigs = [];

                for (let game of formData.games) {
                    const players = document.querySelector(`[name="players_${game}"]`).value.trim();
                    const category = document.querySelector(`[name="category_${game}"]`).value;
                    const environment = document.querySelector(`[name="environment_${game}"]`).value;

                    if (!players || !category || !environment) {
                        error.innerText = `Please complete all fields for ${game}.`;
                        return false;
                    }

                    formData.gameConfigs.push({
                        gameName: game,
                        players: parseInt(players),
                        category: category,
                        environment: environment
                    });
                }

            }

            if (step === 3) {
                const first = document.getElementById('first-place').value.trim();
                const second = document.getElementById('second-place').value.trim();
                const third = document.getElementById('third-place').value.trim();

                if (!first || !second || !third) {
                    error.innerText = 'Please fill in all point values.';
                    return false;
                }

                formData.points.first = parseInt(first);
                formData.points.second = parseInt(second);
                formData.points.third = parseInt(third);
            }

            return true;
        }



        nextBtn.addEventListener("click", (e) => {
            e.preventDefault();

            if (!validateStep(currentStep)) return;

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

            submitBtn.disabled = true;
            submitBtn.innerText = 'Submitting...';
            previousBtn.disabled = true;

            fetch('process_setup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        error.innerText = data.message || "An error occurred.";
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit';
                        previousBtn.disabled = false;
                    }
                })
                .catch(err => {
                    error.innerText = "An error occurred.";
                    console.error(err);
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Submit';
                    previousBtn.disabled = false;
                });
        });


        // Highlight logic
        function setupBadgeClicks() {
            const badges = document.querySelectorAll('.game-badge');
            badges.forEach(badge => {
                const checkbox = badge.querySelector('input[type="checkbox"]');
                badge.addEventListener('click', () => {
                    checkbox.checked = !checkbox.checked;
                    badge.classList.toggle('active', checkbox.checked);
                });
            });
        }

        // Others logic
        if (othersCheckbox) {
            othersCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    otherGameInput.classList.remove('hide');
                    otherGameInput.focus();
                } else {
                    otherGameInput.classList.add('hide');
                    otherGameInput.value = '';
                }
            });
        }

        otherGameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const gameName = otherGameInput.value.trim();
                if (gameName !== '') {
                    addCustomGame(gameName);
                    otherGameInput.value = '';
                    otherGameInput.classList.add('hide');
                    othersCheckbox.checked = false;
                    const parentBadge = othersCheckbox.closest('.game-badge');
                    parentBadge.classList.remove('active');
                }
            }
        });

        // Dynamically add a custom game badge
        function addCustomGame(gameName) {
            const newBadge = document.createElement('label');
            newBadge.className = 'game-badge active';

            const newCheckbox = document.createElement('input');
            newCheckbox.type = 'checkbox';
            newCheckbox.value = gameName;
            newCheckbox.checked = true;

            const icon = document.createElement('i');
            icon.className = 'fas fa-star'; // Icon for custom games

            newBadge.appendChild(newCheckbox);
            newBadge.appendChild(icon);
            newBadge.append(` ${gameName}`);

            const othersBadge = document.getElementById('others-checkbox').closest('label');

            gamesContainer.insertBefore(newBadge, othersBadge);

            newBadge.addEventListener('click', () => {
                newCheckbox.checked = !newCheckbox.checked;
                newBadge.classList.toggle('active', newCheckbox.checked);
            });
        }
    </script>

</body>

</html>