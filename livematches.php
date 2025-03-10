<?php

session_start();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Matches</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</head>

<body>
    <!-- Navbar -->
    <?php
    include 'navbarhome.php'; ?>

    <div class="page-header-live">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Live Matches</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Watch matches as they happen in real-time</p>
        </div>
    </div>

    <div class="container pb-4">
        <div class="match-results">
        </div>
    </div>

    <?php include 'footerhome.php' ?>

    <script>
        function fetchLiveScores() {
            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                return {
                    department_id: params.get("department_id"),
                    grade_level: params.get("grade_level"),
                };
            }

            const {
                department_id,
                grade_level
            } = getUrlParams();

            $.ajax({
                url: 'fetch_live_scores.php',
                method: 'GET',
                dataType: 'json',
                data: {
                    department_id: department_id,
                    grade_level: grade_level
                },
                success: function(data) {
                    const matchResultsContainer = $('.match-results');
                    matchResultsContainer.empty();

                    if (data.error) {
                        matchResultsContainer.html(`
                        <div class="text-center p-4">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <h3 class="h5 mb-2">Error</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">${data.message}</p>
                        </div>
                    `);
                        return;
                    }

                    // Ensure data is an array
                    const matches = Array.isArray(data) ? data : [data];

                    if (matches.length === 0) {
                        matchResultsContainer.html(`
                        <div class="text-center p-4">
                            <i class="fas fa-basketball-ball" style="color: #808080;"></i>
                            <h3 class="h5 mb-2">No Live Matches</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">There are no ongoing matches at the moment. Check back later!</p>
                        </div>
                    `);
                        return;
                    }

                    matches.forEach(match => {
                        const liveIndicator = `<span class="badge bg-success">Live</span>`;

                        const renderAdditionalInfo = (team, additionalInfo, sourceTable) => {
                            let additionalInfoHtml = '';

                            switch (sourceTable) {
                                case 'live_scores':
                                    if (additionalInfo.period) {
                                        additionalInfoHtml += `
                                            <div class="stat-item">
                                                <span class="stat-label">Period</span>
                                                <span>${additionalInfo.period}</span>
                                            </div>
                                        `;
                                    }
                                    if (additionalInfo.timeouts !== null) {
                                        additionalInfoHtml += `
                                            <div class="stat-item">
                                                <span class="stat-label">Timeouts</span>
                                                <span>${additionalInfo.timeouts}</span>
                                            </div>
                                        `;
                                    }
                                    break;

                                case 'live_set_scores':
                                    if (additionalInfo.sets_won !== null) {
                                        additionalInfoHtml += `
                                            <div class="stat-item">
                                                <span class="stat-label">Sets Won</span>
                                                <span>${additionalInfo.sets_won}</span>
                                            </div>
                                        `;
                                    }
                                    if (additionalInfo.current_set) {
                                        additionalInfoHtml += `
                                            <div class="stat-item">
                                                <span class="stat-label">Current Set</span>
                                                <span>${additionalInfo.current_set}</span>
                                            </div>
                                        `;
                                    }
                                    if (additionalInfo.timeouts !== null) {
                                        additionalInfoHtml += `
                                            <div class="stat-item">
                                                <span class="stat-label">Timeouts</span>
                                                <span>${additionalInfo.timeouts}</span>
                                            </div>
                                        `;
                                    }
                                    break;
                            }

                            return additionalInfoHtml;
                        };

                        const renderVsSection = (match) => {
                            let vsContent = '<div class="vs-section">';
                            vsContent += '<div>VS</div>';

                            if (match.source_table === 'live_scores' && match.teamA.additional_info.period) {
                                vsContent += `
                                    <div class="period-info">
                                        Period ${match.teamA.additional_info.period}
                                    </div>
                                    ${match.teamA.additional_info.timer ? `
                                        <div class="timer-display">
                                            ${match.teamA.additional_info.timer}
                                        </div>
                                    ` : ''}
                                `;
                            } else if (match.source_table === 'live_set_scores' && match.teamA.additional_info.current_set) {
                                vsContent += `
                                    <div class="period-info">
                                        Set ${match.teamA.additional_info.current_set}
                                    </div>
                                `;
                            }

                            vsContent += '</div>';
                            return vsContent;
                        };

                        const matchCard = `
<div class="match-result-card">
    <div class="match-header">
        <div class="d-flex align-items-center">
            <span class="game-icon">
                <i class="fas fa-basketball-ball"></i>
            </span>
            <h5 class="match-title">${match.game_name}</h5>
        </div>
        <div class="match-date">
            <i class="far fa-calendar-alt"></i>
            ${match.formatted_date || 'Date Not Available'}
        </div>
    </div>
    <div>
        <div class="live-indicator d-flex justify-content-between align-items-center" style="margin-left: 10px; margin-right: 10px;">
            <div class="live-indicator-item">
                ${liveIndicator}
            </div>
            <button onclick="viewStream(${match.schedule_id})" class="btn btn-sm btn-danger">
                <i class="fas fa-video"></i>Watch Live
            </button>
        </div>
    </div>

    <div class="match-body">
        <div class="row align-items-center">
            <div class="col-md-5">
                <div class="team-section">
                    <div class="team-name">${match.teamA.name}</div>
                    <div class="team-score">${match.teamA.score}</div>
                    <div class="stats-box">
                        ${renderAdditionalInfo(match.teamA, match.teamA.additional_info, match.source_table)}
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                ${renderVsSection(match)}
            </div>
            <div class="col-md-5">
                <div class="team-section">
                    <div class="team-name">${match.teamB.name}</div>
                    <div class="team-score">${match.teamB.score}</div>
                    <div class="stats-box">
                        ${renderAdditionalInfo(match.teamB, match.teamB.additional_info, match.source_table)}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
`;
                        matchResultsContainer.append(matchCard);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching live scores:', error);
                    $('.match-results').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-exclamation-circle text-danger"></i>
                        <h3 class="h5 mb-2">Error</h3>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Failed to fetch live scores. Please try again later.</p>
                    </div>
                `);
                }
            });
        }

        // Function to open stream viewer
        function viewStream(scheduleId) {
            // Convert scheduleId to string for consistency
            scheduleId = String(scheduleId);
            console.log('Opening stream viewer for schedule ID:', scheduleId);

            // Create modal with video player
            const modal = $(`
                <div class="modal fade" id="streamModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Live Stream</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="streamStatus" class="alert d-none"></div>
                                <div class="video-container">
                                    <video id="streamVideo" class="w-100" autoplay muted playsinline></video>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).appendTo('body');

            // Initialize Bootstrap modal
            const modalInstance = new bootstrap.Modal(modal[0], {
                backdrop: 'static',
                keyboard: false
            });

            // Connect to WebSocket when modal is shown
            modal.on('shown.bs.modal', function() {
                console.log('Modal shown, initializing streaming context');
                const streamingContext = {
                    ws: null,
                    reconnectAttempts: 0,
                    maxReconnectAttempts: 5,
                    isModalOpen: true,
                    mediaSource: null,
                    sourceBuffer: null,
                    queue: [],
                    isSourceBufferActive: false,
                    pendingInit: false,
                    scheduleId: scheduleId,
                    hasReceivedFirstChunk: false
                };

                const statusDiv = document.getElementById('streamStatus');
                const videoElement = document.getElementById('streamVideo');

                function updateStatus(message, isError = false) {
                    console.log('Status update:', message, isError ? '(error)' : '');
                    if (statusDiv) {
                        statusDiv.className = `alert ${isError ? 'alert-danger' : 'alert-info'} d-none`;
                        statusDiv.textContent = message;
                        statusDiv.style.display = 'block';
                    }
                    if (videoElement) {
                        videoElement.style.display = isError || !streamingContext.hasReceivedFirstChunk ? 'none' : 'block';
                    }
                }

                function initializeMediaSource() {
                    if (!streamingContext.isModalOpen || streamingContext.pendingInit) {
                        console.log('Skipping MediaSource initialization - modal closed or init pending');
                        return;
                    }

                    console.log('Initializing MediaSource');
                    const video = document.getElementById('streamVideo');
                    if (!video) {
                        console.error('Video element not found');
                        return;
                    }

                    try {
                        // Clear existing MediaSource if any
                        if (streamingContext.mediaSource) {
                            console.log('Cleaning up existing MediaSource');
                            if (streamingContext.sourceBuffer) {
                                try {
                                    streamingContext.mediaSource.removeSourceBuffer(streamingContext.sourceBuffer);
                                } catch (e) {
                                    // Ignore removal errors
                                }
                            }
                            if (streamingContext.mediaSource.readyState === 'open') {
                                try {
                                    streamingContext.mediaSource.endOfStream();
                                } catch (e) {
                                    console.error('Error ending media stream:', e);
                                }
                            }
                            streamingContext.mediaSource = null;
                            streamingContext.sourceBuffer = null;
                            streamingContext.isSourceBufferActive = false;
                        }

                        // Reset video element
                        video.pause();
                        video.currentTime = 0;
                        if (video.src) {
                            URL.revokeObjectURL(video.src);
                        }
                        video.src = '';
                        video.load();

                        // Configure video element
                        video.autoplay = true;
                        video.muted = true; // Mute initially to allow autoplay
                        video.playsInline = true;
                        video.style.display = 'block';
                        video.style.background = '#000';
                        video.style.minHeight = '360px'; // Ensure video has visible height

                        // Add event listeners
                        video.onplay = () => console.log('Video started playing');
                        video.onpause = () => console.log('Video paused');
                        video.onwaiting = () => console.log('Video buffering');
                        video.onplaying = () => {
                            console.log('Video is now playing');
                            video.style.display = 'block';
                            updateStatus('');
                        };
                        video.onerror = (e) => {
                            console.error('Video error:', video.error);
                            updateStatus('Video playback error', true);
                        };

                        streamingContext.pendingInit = true;
                        streamingContext.mediaSource = new MediaSource();
                        streamingContext.mediaSource.addEventListener('sourceopen', function sourceOpenHandler() {
                            if (!streamingContext.isModalOpen) {
                                console.log('Modal closed during MediaSource initialization');
                                streamingContext.pendingInit = false;
                                return;
                            }

                            console.log('MediaSource opened');
                            try {
                                const mimeType = 'video/webm;codecs=vp8';
                                if (!MediaSource.isTypeSupported(mimeType)) {
                                    throw new Error('Unsupported MIME type: ' + mimeType);
                                }
                                console.log('Adding SourceBuffer with MIME type:', mimeType);
                                streamingContext.sourceBuffer = streamingContext.mediaSource.addSourceBuffer(mimeType);
                                streamingContext.sourceBuffer.mode = 'sequence';
                                streamingContext.isSourceBufferActive = true;
                                console.log('SourceBuffer created and initialized');

                                // Set up updateend event handler
                                streamingContext.sourceBuffer.addEventListener('updateend', () => {
                                    if (streamingContext.isSourceBufferActive) {
                                        console.log('SourceBuffer update ended, processing queue');
                                        processQueue();

                                        // Try to play if not already playing
                                        if (video.paused) {
                                            console.log('Attempting to play video after buffer update');
                                            video.play().catch(e => console.log('Play after update failed:', e));
                                        }
                                    }
                                });

                                streamingContext.sourceBuffer.addEventListener('error', (e) => {
                                    console.error('SourceBuffer error:', e);
                                });

                                // Process any queued data
                                processQueue();
                            } catch (e) {
                                console.error('Error initializing SourceBuffer:', e);
                                updateStatus('Error initializing video player: ' + e.message, true);
                            }
                            streamingContext.pendingInit = false;

                            // Remove the handler after first use
                            streamingContext.mediaSource.removeEventListener('sourceopen', sourceOpenHandler);
                        });

                        streamingContext.mediaSource.addEventListener('sourceclose', () => {
                            console.log('MediaSource closed');
                            streamingContext.isSourceBufferActive = false;
                            streamingContext.pendingInit = false;
                        });

                        streamingContext.mediaSource.addEventListener('error', (e) => {
                            console.error('MediaSource error:', e);
                        });

                        // Create and set the MediaSource URL
                        const objectUrl = URL.createObjectURL(streamingContext.mediaSource);
                        console.log('Created new MediaSource with URL:', objectUrl);
                        video.src = objectUrl;

                        // Try to start playing immediately
                        video.play().catch(e => console.log('Initial play failed:', e));
                    } catch (e) {
                        console.error('Error initializing MediaSource:', e);
                        streamingContext.pendingInit = false;
                        updateStatus('Error initializing video player: ' + e.message, true);
                    }
                }

                function processQueue() {
                    if (!streamingContext.isModalOpen || !streamingContext.isSourceBufferActive) {
                        console.log('Skipping queue processing - modal closed or source buffer inactive');
                        return;
                    }

                    if (!streamingContext.sourceBuffer || streamingContext.sourceBuffer.updating) {
                        console.log('Source buffer not ready or currently updating');
                        return;
                    }

                    console.log('Processing queue, length:', streamingContext.queue.length);
                    let processedChunks = 0;

                    while (streamingContext.queue.length > 0 && !streamingContext.sourceBuffer.updating) {
                        const data = streamingContext.queue[0]; // Peek at first item
                        try {
                            console.log('Attempting to append buffer of size:', data.length);
                            streamingContext.sourceBuffer.appendBuffer(data);
                            console.log('Successfully appended buffer');
                            streamingContext.queue.shift(); // Remove only after successful append
                            processedChunks++;

                            // Break if we've processed enough chunks to avoid blocking
                            if (processedChunks >= 5) {
                                console.log('Processed maximum chunks per cycle');
                                break;
                            }
                        } catch (e) {
                            console.error('Error appending buffer:', e);
                            if (e.name === 'QuotaExceededError') {
                                console.log('Buffer full, waiting for more space');
                                break;
                            } else if (e.name === 'InvalidStateError') {
                                console.log('SourceBuffer removed or invalid state, reinitializing...');
                                initializeMediaSource();
                                return;
                            } else {
                                console.warn('Removing problematic chunk from queue');
                                streamingContext.queue.shift(); // Remove bad chunk
                            }
                        }
                    }
                    console.log('Queue processing complete, remaining chunks:', streamingContext.queue.length);
                }

                function connectWebSocket() {
                    if (!streamingContext.isModalOpen) {
                        console.log('Skipping WebSocket connection - modal closed');
                        return;
                    }
                    if (streamingContext.reconnectAttempts >= streamingContext.maxReconnectAttempts) {
                        console.error('Max reconnection attempts reached');
                        updateStatus('Connection failed. Please try again later.', true);
                        return;
                    }

                    console.log('Connecting to WebSocket server');
                    streamingContext.ws = new WebSocket('ws://localhost:8090');

                    streamingContext.ws.onopen = () => {
                        if (!streamingContext.isModalOpen) {
                            console.log('Modal closed after WebSocket connection, closing socket');
                            streamingContext.ws.close();
                            return;
                        }
                        console.log('WebSocket connected');
                        streamingContext.reconnectAttempts = 0;

                        // Send schedule ID to server
                        const initMessage = {
                            type: 'init',
                            scheduleId: streamingContext.scheduleId,
                            role: 'viewer'
                        };
                        console.log('Sending init message:', initMessage);
                        streamingContext.ws.send(JSON.stringify(initMessage));

                        updateStatus('Connected. Waiting for stream...');
                    };

                    streamingContext.ws.onmessage = async (event) => {
                        if (!streamingContext.isModalOpen) return;
                        try {
                            const data = JSON.parse(event.data);
                            console.log('Received message:', data.type, data.scheduleId === streamingContext.scheduleId);

                            if (data.type === 'welcome') {
                                console.log('Received welcome message:', data.message);
                            } else if (data.type === 'video' && data.scheduleId === streamingContext.scheduleId) {
                                console.log('Received video chunk, size:', data.data.length);
                                try {
                                    // Convert array back to binary data
                                    const uint8Array = new Uint8Array(data.data);

                                    // If we haven't initialized MediaSource yet, do it now
                                    if (!streamingContext.mediaSource ||
                                        streamingContext.mediaSource.readyState !== 'open' ||
                                        !streamingContext.isSourceBufferActive) {
                                        console.log('Initializing MediaSource for first chunk');
                                        initializeMediaSource();
                                        // Wait for MediaSource to open
                                        if (!streamingContext.mediaSource) {
                                            console.log('Waiting for MediaSource to be created...');
                                            await new Promise(resolve => setTimeout(resolve, 100));
                                        }
                                        if (streamingContext.mediaSource) {
                                            await new Promise(resolve => {
                                                if (streamingContext.mediaSource.readyState === 'open') {
                                                    resolve();
                                                } else {
                                                    streamingContext.mediaSource.addEventListener('sourceopen', resolve, {
                                                        once: true
                                                    });
                                                }
                                            });
                                            console.log('MediaSource opened, readyState:', streamingContext.mediaSource.readyState);
                                        }
                                    }

                                    if (data.isFirstChunk) {
                                        console.log('Received first chunk');
                                        streamingContext.hasReceivedFirstChunk = true;
                                        updateStatus('');
                                    }

                                    // Add to queue
                                    streamingContext.queue.push(uint8Array);
                                    console.log('Added chunk to queue, length:', streamingContext.queue.length);

                                    // Try to process queue
                                    if (streamingContext.isSourceBufferActive &&
                                        streamingContext.sourceBuffer &&
                                        !streamingContext.sourceBuffer.updating) {
                                        processQueue();
                                    } else {
                                        console.log('Cannot process queue yet:', {
                                            isSourceBufferActive: streamingContext.isSourceBufferActive,
                                            hasSourceBuffer: !!streamingContext.sourceBuffer,
                                            isUpdating: streamingContext.sourceBuffer ? streamingContext.sourceBuffer.updating : 'N/A'
                                        });
                                    }
                                } catch (e) {
                                    console.error('Error processing video data:', e);
                                    updateStatus('Error processing video data', true);
                                }
                            }
                        } catch (e) {
                            console.error('Error handling message:', e);
                            updateStatus('Error handling stream data', true);
                        }
                    };

                    streamingContext.ws.onerror = (error) => {
                        console.error('WebSocket Error:', error);
                        updateStatus('Connection error occurred', true);
                    };

                    streamingContext.ws.onclose = () => {
                        if (!streamingContext.isModalOpen) return;
                        console.log('WebSocket closed. Attempting to reconnect...');
                        updateStatus('Connection lost. Reconnecting...');
                        if (streamingContext.reconnectAttempts < streamingContext.maxReconnectAttempts) {
                            streamingContext.reconnectAttempts++;
                            setTimeout(connectWebSocket, 1000);
                        } else {
                            updateStatus('Connection lost. Please try again later.', true);
                        }
                    };

                    // Store context for cleanup
                    modal.data('streamingContext', streamingContext);
                }

                // Initial connection
                connectWebSocket();
            });

            // Cleanup when modal is hidden
            modal.on('hide.bs.modal', function() {
                console.log('Modal hiding, cleaning up resources');
                const context = modal.data('streamingContext');
                if (context) {
                    context.isModalOpen = false;
                    context.isSourceBufferActive = false;

                    if (context.ws) {
                        context.ws.close();
                    }

                    if (context.mediaSource && context.mediaSource.readyState === 'open') {
                        if (context.sourceBuffer) {
                            try {
                                context.mediaSource.removeSourceBuffer(context.sourceBuffer);
                            } catch (e) {
                                console.error('Error removing source buffer:', e);
                            }
                        }
                        try {
                            context.mediaSource.endOfStream();
                        } catch (e) {
                            console.error('Error ending media stream:', e);
                        }
                    }

                    // Clear queue
                    context.queue = [];
                }

                const video = document.getElementById('streamVideo');
                if (video) {
                    video.pause();
                    video.src = '';
                    video.load();
                }
            });

            // Remove modal from DOM after it's hidden
            modal.on('hidden.bs.modal', function() {
                console.log('Modal hidden, removing from DOM');
                modal.remove();
            });

            // Show the modal
            modalInstance.show();
        }

        // Start fetching when document is ready
        $(document).ready(function() {
            fetchLiveScores();
            setInterval(fetchLiveScores, 5000);
        });
    </script>
</body>

</html>