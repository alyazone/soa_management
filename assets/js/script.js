// SOA Management System JavaScript

document.addEventListener("DOMContentLoaded", () => {
    // Initialize DataTables if available
    if (typeof $.fn.DataTable !== "undefined" && document.getElementById("dataTable")) {
      // Check if jQuery is loaded
      if (typeof jQuery == "undefined") {
        console.error("jQuery is not loaded. DataTables may not function correctly.")
        return // Exit if jQuery is not loaded
      }
  
      $("#dataTable").DataTable({
        responsive: true,
        order: [[0, "desc"]],
      })
    }
  
    // Initialize date pickers if available
    if (typeof $.fn.datepicker !== "undefined") {
      $(".datepicker").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
      })
    }
  
    // Handle file input change to show selected filename
    const fileInputs = document.querySelectorAll(".custom-file-input")
    fileInputs.forEach((input) => {
      input.addEventListener("change", function (e) {
        const fileName = this.files[0].name
        const nextSibling = e.target.nextElementSibling
        nextSibling.innerText = fileName
      })
    })
  
    // Handle status change in SOA list
    const statusSelects = document.querySelectorAll(".status-select")
    statusSelects.forEach((select) => {
      select.addEventListener("change", function () {
        const soaId = this.getAttribute("data-soa-id")
        const newStatus = this.value
  
        if (confirm("Are you sure you want to change the status?")) {
          // Send AJAX request to update status
          fetch("update_status.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `soa_id=${soaId}&status=${newStatus}`,
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                // Update badge color
                const badge = document.querySelector(`#status-badge-${soaId}`)
                badge.className = `badge badge-${newStatus === "Paid" ? "success" : newStatus === "Overdue" ? "danger" : "warning"}`
                badge.textContent = newStatus
  
                // Show success message
                alert("Status updated successfully!")
              } else {
                alert("Failed to update status: " + data.message)
              }
            })
            .catch((error) => {
              console.error("Error:", error)
              alert("An error occurred while updating the status.")
            })
        } else {
          // Reset select to original value if canceled
          this.value = this.getAttribute("data-original-value")
        }
      })
    })
  
    // Print SOA button
    const printButtons = document.querySelectorAll(".btn-print")
    printButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.preventDefault()
        window.print()
      })
    })
  
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll(".btn-delete")
    deleteButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        if (!confirm("Are you sure you want to delete this record? This action cannot be undone.")) {
          e.preventDefault()
        }
      })
    })
  })
  