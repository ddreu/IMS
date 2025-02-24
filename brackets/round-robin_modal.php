<!-- Round Robin Modal -->
<div id="roundRobinModal" class="modal fade" tabindex="-1" aria-labelledby="roundRobinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roundRobinModalLabel">Round Robin Tournament Schedule</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add scoring settings section -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Tournament Scoring Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="winPoints">Win Points</label>
                                    <input type="number" class="form-control" id="winPoints" value="3">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="drawPoints">Draw Points</label>
                                    <input type="number" class="form-control" id="drawPoints" value="1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lossPoints">Loss Points</label>
                                    <input type="number" class="form-control" id="lossPoints" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bonusPoints">Bonus Points</label>
                                    <input type="number" class="form-control" id="bonusPoints" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 text-end">
                    <button type="button" class="btn btn-warning" id="resetTeams">
                        <i class="fas fa-undo"></i> Reset Teams to Default
                    </button>
                </div>
                <table class="table" id="roundRobinTable">
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRoundRobin">Save Tournament</button>
            </div>
        </div>
    </div>
</div>


<!-- View Round Robin Modal -->
<div id="viewRoundRobinModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Round Robin Tournament Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tournament Info Section -->
                <div class="tournament-info mb-4">
                    <h6>Tournament Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Game:</strong> <span id="tournamentGame"></span></p>
                            <p><strong>Department:</strong> <span id="tournamentDept"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span id="tournamentStatus"></span></p>
                            <p><strong>Total Teams:</strong> <span id="tournamentTeams"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Scoring Rules Section - Modified to be editable -->
                <div class="scoring-rules mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6>Scoring Rules</h6>
                        <button class="btn btn-primary btn-sm" id="savePoints">
                            <i class="fas fa-save"></i> Save Points
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="viewWinPoints">Win Points</label>
                                <input type="number" class="form-control" id="viewWinPoints">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="viewDrawPoints">Draw Points</label>
                                <input type="number" class="form-control" id="viewDrawPoints">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="viewLossPoints">Loss Points</label>
                                <input type="number" class="form-control" id="viewLossPoints">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="viewBonusPoints">Bonus Points</label>
                                <input type="number" class="form-control" id="viewBonusPoints">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Standings Section -->
                <div class="standings-section mb-4">
                    <h6>Tournament Standings</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Team Name</th>
                                    <th>Played</th>
                                    <th>Wins</th>
                                    <th>Draws</th>
                                    <th>Lost</th>
                                    <th>Bonus Points</th>
                                    <th>Total Points</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="standingsTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Matches Section (Modified) -->
                <div class="matches-section">
                    <h6>Match Schedule</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Round</th>
                                    <th>Match</th>
                                    <th>Team A</th>
                                    <th>Score</th>
                                    <th>Team B</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="matchesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>