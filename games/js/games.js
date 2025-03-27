function openAsCommittee(gameId) {
  $.ajax({
    url: "open_as_committee.php",
    type: "POST",
    data: { game_id: gameId },
    success: function (response) {
      const res = JSON.parse(response);
      if (res.success) {
        // Redirect to the committee dashboard
        window.location.href = "../committee/committeedashboard.php";
      } else {
        alert(res.message);
      }
    },
    error: function () {
      alert("An error occurred while opening as committee.");
    },
  });
}
