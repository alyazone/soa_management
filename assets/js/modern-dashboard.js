// Modern Dashboard JavaScript
class ModernDashboard {
  constructor() {
    this.init()
  }

  init() {
    this.initSidebar()
    this.initInteractions()
    this.initChartFilters()
    this.initTableInteractions()
    this.initDateRangePicker()
    this.initQuickActions()
  }

  initSidebar() {
    const sidebarToggle = document.getElementById("sidebarToggle")
    const sidebarCollapseBtn = document.getElementById("sidebarCollapseBtn")
    const sidebar = document.getElementById("sidebar")
    const body = document.body

    // Mobile sidebar toggle
    if (sidebarToggle) {
      sidebarToggle.addEventListener("click", () => {
        body.classList.toggle("sidebar-open")
      })
    }

    // Desktop sidebar collapse
    if (sidebarCollapseBtn) {
      sidebarCollapseBtn.addEventListener("click", () => {
        body.classList.toggle("sidebar-collapsed")
        localStorage.setItem("sidebarCollapsed", body.classList.contains("sidebar-collapsed"))
      })
    }

    // Restore sidebar state
    if (localStorage.getItem("sidebarCollapsed") === "true") {
      body.classList.add("sidebar-collapsed")
    }

    // Submenu toggles
    const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]')
    submenuToggles.forEach((toggle) => {
      toggle.addEventListener("click", (e) => {
        e.preventDefault()
        const submenu = toggle.nextElementSibling
        if (submenu) {
          submenu.classList.toggle("show")
        }
      })
    })

    // Close sidebar on mobile when clicking outside
    document.addEventListener("click", (e) => {
      if (window.innerWidth <= 1024 && body.classList.contains("sidebar-open")) {
        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
          body.classList.remove("sidebar-open")
        }
      }
    })
  }

  initInteractions() {
    // Stat card click handlers
    const statCards = document.querySelectorAll(".stat-card")
    statCards.forEach((card) => {
      card.addEventListener("click", () => {
        const target = card.dataset.target
        if (target) {
          window.location.href = target
        }
      })
    })

    // Table row click handlers
    const clickableRows = document.querySelectorAll(".table-row-clickable")
    clickableRows.forEach((row) => {
      row.addEventListener("click", (e) => {
        if (!e.target.closest("button")) {
          const href = row.dataset.href
          if (href) {
            window.location.href = href
          }
        }
      })
    })
  }

  initChartFilters() {
    const filterTabs = document.querySelectorAll(".filter-tab")
    filterTabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        // Remove active class from all tabs
        filterTabs.forEach((t) => t.classList.remove("active"))
        // Add active class to clicked tab
        tab.classList.add("active")

        const filter = tab.dataset.filter
        this.filterChart(filter)
      })
    })
  }

  filterChart(filter) {
    // This would integrate with your chart library to filter data
    console.log("Filtering chart by:", filter)

    // Example: Update chart based on filter
    // window.ApexCharts.exec('trendsChart', 'updateSeries', newData);
  }

  initTableInteractions() {
    // Table refresh buttons
    const refreshButtons = document.querySelectorAll('[onclick^="refreshTable"]')
    refreshButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.preventDefault()
        const tableId = button.getAttribute("onclick").match(/refreshTable$$'(.+)'$$/)[1]
        this.refreshTable(tableId)
      })
    })
  }

  refreshTable(tableId) {
    const table = document.getElementById(tableId)
    if (table) {
      table.classList.add("loading")

      // Simulate API call
      setTimeout(() => {
        table.classList.remove("loading")
        this.showNotification("Table refreshed successfully", "success")
      }, 1000)
    }
  }

  initDateRangePicker() {
    const dateRangePicker = document.getElementById("dateRangePicker")
    if (dateRangePicker) {
      dateRangePicker.addEventListener("click", () => {
        // This would integrate with a date picker library
        this.showDateRangePicker()
      })
    }
  }

  showDateRangePicker() {
    // Placeholder for date range picker integration
    console.log("Opening date range picker...")

    // Example: Update date range text
    const dateRangeText = document.getElementById("dateRangeText")
    if (dateRangeText) {
      dateRangeText.textContent = "Custom Range"
    }
  }

  initQuickActions() {
    const quickActionBtns = document.querySelectorAll(".quick-action-btn")
    quickActionBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        // Add click animation
        btn.style.transform = "scale(0.95)"
        setTimeout(() => {
          btn.style.transform = ""
        }, 150)
      })
    })
  }

  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div")
    notification.className = `notification notification-${type}`
    notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === "success" ? "check-circle" : "info-circle"}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `

    // Add to page
    document.body.appendChild(notification)

    // Auto remove after 3 seconds
    setTimeout(() => {
      notification.remove()
    }, 3000)

    // Close button handler
    notification.querySelector(".notification-close").addEventListener("click", () => {
      notification.remove()
    })
  }

  // Chart functions
  refreshChart(chartId) {
    console.log("Refreshing chart:", chartId)
    this.showNotification("Chart refreshed successfully", "success")
  }

  exportChart(chartId) {
    console.log("Exporting chart:", chartId)
    // This would integrate with ApexCharts export functionality
    // window.ApexCharts.exec(chartId, 'dataURI').then(({ imgURI }) => {
    //     const link = document.createElement('a');
    //     link.href = imgURI;
    //     link.download = `${chartId}.png`;
    //     link.click();
    // });
    this.showNotification("Chart exported successfully", "success")
  }

  // Utility functions
  formatCurrency(amount) {
    return new Intl.NumberFormat("en-MY", {
      style: "currency",
      currency: "MYR",
    }).format(amount)
  }

  formatDate(date) {
    return new Intl.DateTimeFormat("en-MY").format(new Date(date))
  }

  // API functions
  async fetchDashboardData(dateRange = null) {
    try {
      const params = new URLSearchParams()
      if (dateRange) {
        params.append("start", dateRange.start)
        params.append("end", dateRange.end)
      }

      const response = await fetch(`dashboard_data.php?${params}`)
      const data = await response.json()

      if (data.error) {
        throw new Error(data.error)
      }

      return data
    } catch (error) {
      console.error("Error fetching dashboard data:", error)
      this.showNotification("Error loading dashboard data", "error")
      return null
    }
  }

  async updateDashboard(dateRange = null) {
    const data = await this.fetchDashboardData(dateRange)
    if (data) {
      this.updateStatsCards(data.summary)
      this.updateCharts(data)
      this.updateTables(data)
    }
  }

  updateStatsCards(summary) {
    // Update stat card values
    const statValues = document.querySelectorAll(".stat-value")
    if (summary && statValues.length >= 4) {
      statValues[0].textContent = summary.client_count.toLocaleString()
      statValues[1].textContent = summary.supplier_count.toLocaleString()
      statValues[2].textContent = summary.soa_count.toLocaleString()
      statValues[3].textContent = summary.pending_claims.toLocaleString()
    }
  }

  updateCharts(data) {
    // Update charts with new data
    if (data.soa_status) {
      // Update SOA status chart
      window.ApexCharts.exec("soaChart", "updateSeries", data.soa_status.values)
    }

    if (data.claims_status) {
      // Update claims status chart
      window.ApexCharts.exec("claimsChart", "updateSeries", [
        {
          data: data.claims_status.values,
        },
      ])
    }

    if (data.monthly_trends) {
      // Update monthly trends chart
      window.ApexCharts.exec("trendsChart", "updateSeries", [
        {
          name: "SOAs",
          data: data.monthly_trends.soa_counts,
        },
        {
          name: "Claims",
          data: data.monthly_trends.claim_counts,
        },
      ])
    }
  }

  updateTables(data) {
    // Update recent SOAs table
    if (data.recent_soas) {
      this.updateSOATable(data.recent_soas)
    }

    // Update recent claims table
    if (data.recent_claims) {
      this.updateClaimsTable(data.recent_claims)
    }
  }

  updateSOATable(soas) {
    const tbody = document.querySelector("#soaTable tbody")
    if (tbody) {
      tbody.innerHTML = soas
        .map(
          (soa) => `
                <tr class="table-row-clickable" data-href="modules/soa/view.php?id=${soa.soa_id}">
                    <td class="font-medium">${soa.account_number}</td>
                    <td>${soa.client_name}</td>
                    <td class="font-medium">RM ${Number.parseFloat(soa.balance_amount).toLocaleString()}</td>
                    <td>
                        <span class="status-badge status-${soa.status.toLowerCase()}">
                            ${soa.status}
                        </span>
                    </td>
                    <td>
                        <button class="action-btn" onclick="viewSOA(${soa.soa_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `,
        )
        .join("")

      // Re-initialize click handlers
      this.initInteractions()
    }
  }

  updateClaimsTable(claims) {
    const tbody = document.querySelector("#claimsTable tbody")
    if (tbody) {
      tbody.innerHTML = claims
        .map(
          (claim) => `
                <tr class="table-row-clickable" data-href="modules/claims/view.php?id=${claim.claim_id}">
                    <td class="font-medium">${claim.full_name}</td>
                    <td class="font-medium">RM ${Number.parseFloat(claim.amount).toLocaleString()}</td>
                    <td>${new Date(claim.submitted_date).toLocaleDateString()}</td>
                    <td>
                        <span class="status-badge status-${claim.status.toLowerCase()}">
                            ${claim.status}
                        </span>
                    </td>
                    <td>
                        <button class="action-btn" onclick="viewClaim(${claim.claim_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `,
        )
        .join("")

      // Re-initialize click handlers
      this.initInteractions()
    }
  }
}

// Global functions for onclick handlers
function viewSOA(id) {
  window.location.href = `modules/soa/view.php?id=${id}`
}

function viewClaim(id) {
  window.location.href = `modules/claims/view.php?id=${id}`
}

function refreshChart(chartId) {
  if (window.dashboard) {
    window.dashboard.refreshChart(chartId)
  }
}

function exportChart(chartId) {
  if (window.dashboard) {
    window.dashboard.exportChart(chartId)
  }
}

function refreshTable(tableId) {
  if (window.dashboard) {
    window.dashboard.refreshTable(tableId)
  }
}

// Initialize dashboard when DOM is loaded
function initializeDashboard() {
  window.dashboard = new ModernDashboard()
}

// Notification styles
const notificationStyles = `
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    z-index: 1000;
    animation: slideInRight 0.3s ease-out;
}

.notification-success {
    border-left: 4px solid var(--success-color);
}

.notification-error {
    border-left: 4px solid var(--danger-color);
}

.notification-info {
    border-left: 4px solid var(--info-color);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.notification-content i {
    font-size: 1.125rem;
}

.notification-success .notification-content i {
    color: var(--success-color);
}

.notification-error .notification-content i {
    color: var(--danger-color);
}

.notification-info .notification-content i {
    color: var(--info-color);
}

.notification-close {
    background: none;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.notification-close:hover {
    background: var(--gray-100);
    color: var(--gray-600);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .notification {
        right: 10px;
        left: 10px;
        min-width: auto;
    }
}
</style>
`

// Add notification styles to head
document.head.insertAdjacentHTML("beforeend", notificationStyles)
