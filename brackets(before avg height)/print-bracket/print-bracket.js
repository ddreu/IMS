// $('#printBracket').on('click', function() {
//     // Clone the bracket data from the existing bracket
//     var bracketData = $('#bracket-container').data('bracket');
//     console.log('Bracket Data to Print:', bracketData);

//     if (!bracketData) {
//         Swal.fire({
//             icon: 'warning',
//             title: 'Warning',
//             text: 'No bracket data available to print.'
//         });
//         return;
//     }

//     var printWindow = window.open('', '_blank');
//     printWindow.document.write(`
//         <html>
//             <head>
//                 <title>Print Bracket</title>
//                 <!-- Include jQuery -->
//                 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
//                 <!-- Include jQuery Bracket CSS -->
//                 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" />
//                 <!-- Include jQuery Bracket JS -->
//                 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
//                 <style>
//                     body {
//                         font-family: Arial, sans-serif;
//                         padding: 20px;
//                     }
//                     .bracket-container {
//                         display: flex;
//                         justify-content: center;
//                         align-items: center;
//                     }
//                 </style>
//             </head>
//             <body>
//                 <div id="bracket-container"></div>
//                 <script>
//                     // Wait until the document is ready
//                     document.addEventListener('DOMContentLoaded', function() {
//                         // Initialize the bracket in the new window
//                         $('#bracket-container').bracket({
//                             teamWidth: 150,
//                             scoreWidth: 40,
//                             matchMargin: 50,
//                             roundMargin: 50,
//                             init: ${JSON.stringify(bracketData)},
//                             decorator: {
//                                 edit: function() {}, // Disable editing
//                                 render: function(container, data, score, state) {
//                                     container.empty();
//                                     if (data === null) {
//                                         container.append("BYE");
//                                     } else if (data === "TBD") {
//                                         container.append("TBD");
//                                     } else {
//                                         container.append(data);
//                                     }
//                                 }
//                             }
//                         });
//                     });
//                 </script>
//             </body>
//         </html>
//     `);

//     printWindow.document.close(); // Close the document stream
//     printWindow.focus(); // Focus on the window
//     printWindow.print(); // Trigger the print
//     printWindow.close(); // Close after printing
// });


function downloadBracket(bracketId, type) {
    const container = document.getElementById('bracket-container');

    if (!container) {
        console.error('Bracket container not found');
        return;
    }

    html2canvas(container, {
        scale: 2
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('landscape');
        const imgWidth = pdf.internal.pageSize.getWidth();
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        pdf.save(`${type}_bracket_${bracketId}.pdf`);
    }).catch(error => {
        console.error('Error generating PDF:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to generate PDF. Please try again.'
        });
    });
}


// DOUBLE BRAKCET DOWNLOAD

function viewDoubleElimination(bracketId) {
    console.log('View Double Elimination clicked:', bracketId);

    $('#bracket-content').empty();

    fetch('fetch_double_elimination.php?bracket_id=' + bracketId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // TODO: Handle double elimination display here
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load double elimination bracket'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load bracket. Please try again.'
            });
        });
}


//ROUND ROBIN DOWNLOAD

function viewRoundRobin(bracketId) {
    console.log('View Round Robin clicked:', bracketId);

    $('#bracket-content').empty();

    fetch('fetch_round_robin.php?bracket_id=' + bracketId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // TODO: Display round robin data in a table or grid
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load round robin bracket'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load bracket. Please try again.'
            });
        });
}
