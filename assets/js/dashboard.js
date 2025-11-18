// Dashboard Interactive Charts and Functionality using ApexCharts

// Initialize all charts when document is ready
document.addEventListener("DOMContentLoaded", () => {
  // Initialize charts
  initSoaStatusChart()
  initClaimsChart()
  initMonthlyTrendsChart()
  initTopClientsChart()

  // Initialize date range picker
  initDateRangePicker()

  // Add event listeners for drill-down cards
  initDrillDownCards()

  // Initialize data tables with export functionality
  initDataTables()

  const userInfo = document.getElementById('userInfoDropdown');
  if (userInfo) {
    userInfo.addEventListener('click', function(e) {
      e.stopPropagation();
      userInfo.classList.toggle('active');
    });
    // Hide dropdown when clicking outside
    document.addEventListener('click', function() {
      userInfo.classList.remove('active');
    });
    // Optional: keyboard accessibility
    userInfo.addEventListener('blur', function() {
      setTimeout(() => userInfo.classList.remove('active'), 100);
    });
  }
})

// Initialize SOA Status Distribution Chart
function initSoaStatusChart() {
  const element = document.getElementById("soaStatusChart")
  if (!element) return

  fetch("api/dashboard_data.php?data=soa_status")
    .then((response) => response.json())
    .then((data) => {
      const options = {
        series: data.values,
        chart: {
          type: "donut",
          height: 300,
          events: {
            dataPointSelection: (event, chartContext, config) => {
              const status = data.labels[config.dataPointIndex]
              window.location.href = `modules/soa/index.php?status=${status}`
            },
          },
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            animateGradually: {
              enabled: true,
              delay: 150,
            },
            dynamicAnimation: {
              enabled: true,
              speed: 350,
            },
          },
        },
        labels: data.labels,
        colors: ["#4e73df", "#1cc88a", "#e74a3b", "#f6c23e"],
        legend: {
          position: "bottom",
        },
        tooltip: {
          y: {
            formatter: (value, { series, seriesIndex, dataPointIndex, w }) => {
              const total = series.reduce((a, b) => a + b, 0)
              const percentage = ((value / total) * 100).toFixed(1)
              return `${value} (${percentage}%)`
            },
          },
        },
        dataLabels: {
          enabled: true,
          formatter: (val, opts) => {
            const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0)
            const percentage = ((val / total) * 100).toFixed(1)
            return percentage + "%"
          },
        },
        responsive: [
          {
            breakpoint: 480,
            options: {
              chart: {
                width: 200,
              },
              legend: {
                position: "bottom",
              },
            },
          },
        ],
      }

      const chart = new ApexCharts(element, options)
      chart.render()
    })
    .catch((error) => console.error("Error loading SOA status chart data:", error))
}

// Initialize Claims by Status Chart
function initClaimsChart() {
  const element = document.getElementById("claimsChart")
  if (!element) return

  fetch("api/dashboard_data.php?data=claims_status")
    .then((response) => response.json())
    .then((data) => {
      const options = {
        series: [
          {
            name: "Number of Claims",
            data: data.values,
          },
        ],
        chart: {
          type: "bar",
          height: 300,
          events: {
            dataPointSelection: (event, chartContext, config) => {
              const status = data.labels[config.dataPointIndex]
              window.location.href = `modules/claims/index.php?status=${status}`
            },
          },
          toolbar: {
            show: true,
            tools: {
              download: true,
              selection: true,
              zoom: true,
              zoomin: true,
              zoomout: true,
              pan: true,
              reset: true,
            },
          },
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            dynamicAnimation: {
              enabled: true,
              speed: 350,
            },
          },
        },
        plotOptions: {
          bar: {
            borderRadius: 4,
            horizontal: false,
            columnWidth: "55%",
            distributed: true,
            dataLabels: {
              position: "top",
            },
          },
        },
        colors: ["#f6c23e", "#1cc88a", "#e74a3b"],
        dataLabels: {
          enabled: true,
          formatter: (val) => val,
          offsetY: -20,
          style: {
            fontSize: "12px",
            colors: ["#304758"],
          },
        },
        xaxis: {
          categories: data.labels,
          position: "bottom",
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
        },
        yaxis: {
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
          labels: {
            show: true,
            formatter: (val) => val.toFixed(0),
          },
        },
        tooltip: {
          y: {
            formatter: (val) => val + " claims",
          },
        },
      }

      const chart = new ApexCharts(element, options)
      chart.render()
    })
    .catch((error) => console.error("Error loading claims chart data:", error))
}

// Initialize Monthly Trends Chart
function initMonthlyTrendsChart() {
  const element = document.getElementById("monthlyTrendsChart")
  if (!element) return

  fetch("api/dashboard_data.php?data=monthly_trends")
    .then((response) => response.json())
    .then((data) => {
      const options = {
        series: [
          {
            name: "SOAs Issued",
            data: data.soa_counts,
          },
          {
            name: "Claims Submitted",
            data: data.claim_counts,
          },
        ],
        chart: {
          height: 300,
          type: "area",
          toolbar: {
            show: true,
          },
          zoom: {
            enabled: true,
          },
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            animateGradually: {
              enabled: true,
              delay: 150,
            },
            dynamicAnimation: {
              enabled: true,
              speed: 350,
            },
          },
        },
        dataLabels: {
          enabled: false,
        },
        stroke: {
          curve: "smooth",
          width: 2,
        },
        colors: ["#4e73df", "#1cc88a"],
        fill: {
          type: "gradient",
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
            stops: [0, 90, 100],
          },
        },
        markers: {
          size: 4,
          colors: ["#4e73df", "#1cc88a"],
          strokeColors: "#fff",
          strokeWidth: 2,
          hover: {
            size: 7,
          },
        },
        xaxis: {
          categories: data.months,
        },
        yaxis: {
          title: {
            text: "Count",
          },
          labels: {
            formatter: (val) => val.toFixed(0),
          },
        },
        tooltip: {
          shared: true,
          intersect: false,
          y: {
            formatter: (val) => val.toFixed(0),
          },
        },
        legend: {
          position: "top",
          horizontalAlign: "right",
        },
      }

      const chart = new ApexCharts(element, options)
      chart.render()

      // Add filter functionality
      window.filterMonthlyChart = (type) => {
        if (type === "all") {
          chart.showSeries("SOAs Issued")
          chart.showSeries("Claims Submitted")
        } else if (type === "soa") {
          chart.showSeries("SOAs Issued")
          chart.hideSeries("Claims Submitted")
        } else if (type === "claims") {
          chart.hideSeries("SOAs Issued")
          chart.showSeries("Claims Submitted")
        }
      }
    })
    .catch((error) => console.error("Error loading monthly trends chart data:", error))
}

// Initialize Top Clients Chart
function initTopClientsChart() {
  const element = document.getElementById("topClientsChart")
  if (!element) return

  fetch("api/dashboard_data.php?data=top_clients")
    .then((response) => response.json())
    .then((data) => {
      const options = {
        series: [
          {
            name: "Total SOA Amount (RM)",
            data: data.amounts,
          },
        ],
        chart: {
          type: "bar",
          height: 300,
          toolbar: {
            show: true,
          },
          events: {
            dataPointSelection: (event, chartContext, config) => {
              const clientId = data.client_ids[config.dataPointIndex]
              window.location.href = `modules/clients/view.php?id=${clientId}`
            },
          },
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            dynamicAnimation: {
              enabled: true,
              speed: 350,
            },
          },
        },
        plotOptions: {
          bar: {
            borderRadius: 4,
            horizontal: true,
            distributed: false,
            dataLabels: {
              position: "bottom",
            },
          },
        },
        colors: ["#4e73df"],
        dataLabels: {
          enabled: true,
          textAnchor: "start",
          style: {
            colors: ["#fff"],
          },
          formatter: (val, opt) => "RM " + val.toFixed(2),
          offsetX: 0,
          dropShadow: {
            enabled: true,
          },
        },
        stroke: {
          width: 1,
          colors: ["#fff"],
        },
        xaxis: {
          categories: data.client_names,
          labels: {
            formatter: (val) => "RM " + val.toFixed(2),
          },
        },
        yaxis: {
          labels: {
            show: true,
          },
        },
        tooltip: {
          theme: "dark",
          x: {
            show: true,
          },
          y: {
            title: {
              formatter: () => "Amount:",
            },
            formatter: (val) => "RM " + val.toFixed(2),
          },
        },
      }

      const chart = new ApexCharts(element, options)
      chart.render()
    })
    .catch((error) => console.error("Error loading top clients chart data:", error))
}

// Initialize Date Range Picker
function initDateRangePicker() {
  const dateRangePicker = document.getElementById("dateRangePicker")
  if (!dateRangePicker) return

  $(dateRangePicker).daterangepicker(
    {
      ranges: {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Last 7 Days": [moment().subtract(6, "days"), moment()],
        "Last 30 Days": [moment().subtract(29, "days"), moment()],
        "This Month": [moment().startOf("month"), moment().endOf("month")],
        "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
      },
      startDate: moment().subtract(29, "days"),
      endDate: moment(),
      opens: "left",
    },
    (start, end) => {
      $("#dateRangeText").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"))
      updateDashboardData(start.format("YYYY-MM-DD"), end.format("YYYY-MM-DD"))
    },
  )

  // Initial text update
  $("#dateRangeText").html(
    moment().subtract(29, "days").format("MMMM D, YYYY") + " - " + moment().format("MMMM D, YYYY"),
  )
}

// Update dashboard data based on date range
function updateDashboardData(startDate, endDate) {
  // Show loading indicators
  document.querySelectorAll(".chart-container").forEach((container) => {
    container.classList.add("loading")
  })

  // Update summary cards
  fetch(`api/dashboard_data.php?data=summary&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      document.getElementById("clientCount").textContent = data.client_count
      document.getElementById("supplierCount").textContent = data.supplier_count
      document.getElementById("soaCount").textContent = data.soa_count
      document.getElementById("pendingClaimsCount").textContent = data.pending_claims
    })

  // Update SOA status chart
  fetch(`api/dashboard_data.php?data=soa_status&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      if (typeof ApexCharts !== "undefined") {
        ApexCharts.exec("soaStatusChart", "updateOptions", {
          labels: data.labels,
          series: data.values,
        })
      }
      document.querySelector("#soaStatusChart").parentNode.classList.remove("loading")
    })

  // Update claims chart
  fetch(`api/dashboard_data.php?data=claims_status&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      if (typeof ApexCharts !== "undefined") {
        ApexCharts.exec("claimsChart", "updateOptions", {
          xaxis: {
            categories: data.labels,
          },
          series: [
            {
              data: data.values,
            },
          ],
        })
      }
      document.querySelector("#claimsChart").parentNode.classList.remove("loading")
    })

  // Update monthly trends chart
  fetch(`api/dashboard_data.php?data=monthly_trends&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      if (typeof ApexCharts !== "undefined") {
        ApexCharts.exec("monthlyTrendsChart", "updateOptions", {
          xaxis: {
            categories: data.months,
          },
          series: [
            {
              name: "SOAs Issued",
              data: data.soa_counts,
            },
            {
              name: "Claims Submitted",
              data: data.claim_counts,
            },
          ],
        })
      }
      document.querySelector("#monthlyTrendsChart").parentNode.classList.remove("loading")
    })

  // Update top clients chart
  fetch(`api/dashboard_data.php?data=top_clients&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      if (typeof ApexCharts !== "undefined") {
        ApexCharts.exec("topClientsChart", "updateOptions", {
          xaxis: {
            categories: data.client_names,
          },
          series: [
            {
              data: data.amounts,
            },
          ],
        })
      }
      document.querySelector("#topClientsChart").parentNode.classList.remove("loading")
    })

  // Update tables
  updateRecentSoasTable(startDate, endDate)
  updateRecentClaimsTable(startDate, endDate)
}

// Initialize drill-down cards
function initDrillDownCards() {
  const drillDownCards = document.querySelectorAll(".drill-down-card")
  drillDownCards.forEach((card) => {
    card.addEventListener("click", function () {
      const target = this.getAttribute("data-target")
      if (target) {
        window.location.href = target
      }
    })
  })
}

// Initialize DataTables with export functionality
function initDataTables() {
  if (typeof $.fn.DataTable !== "undefined") {
    $(".datatable-export").DataTable({
      dom: "Bfrtip",
      buttons: ["copy", "csv", "excel", "pdf", "print"],
      pageLength: 5,
      responsive: true,
    })
  }
}

// Update Recent SOAs table
function updateRecentSoasTable(startDate, endDate) {
  fetch(`api/dashboard_data.php?data=recent_soas&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      const tableBody = document.querySelector("#recentSoasTable tbody")
      if (!tableBody) return

      tableBody.innerHTML = ""

      if (data.length === 0) {
        const row = document.createElement("tr")
        row.innerHTML = '<td colspan="5" class="text-center">No SOA records found</td>'
        tableBody.appendChild(row)
        return
      }

      data.forEach((soa) => {
        const row = document.createElement("tr")
        row.innerHTML = `
          <td>${soa.account_number}</td>
          <td>${soa.client_name}</td>
          <td>${soa.issue_date}</td>
          <td>RM ${Number.parseFloat(soa.balance_amount).toFixed(2)}</td>
          <td>
            <span class="badge badge-${soa.status === "Paid" ? "success" : soa.status === "Overdue" ? "danger" : "warning"}">
              ${soa.status}
            </span>
          </td>
        `
        row.style.cursor = "pointer"
        row.addEventListener("click", () => {
          window.location.href = `modules/soa/view.php?id=${soa.soa_id}`
        })
        tableBody.appendChild(row)
      })
    })
}

// Update Recent Claims table
function updateRecentClaimsTable(startDate, endDate) {
  fetch(`api/dashboard_data.php?data=recent_claims&start=${startDate}&end=${endDate}`)
    .then((response) => response.json())
    .then((data) => {
      const tableBody = document.querySelector("#recentClaimsTable tbody")
      if (!tableBody) return

      tableBody.innerHTML = ""

      if (data.length === 0) {
        const row = document.createElement("tr")
        row.innerHTML = '<td colspan="4" class="text-center">No claim records found</td>'
        tableBody.appendChild(row)
        return
      }

      data.forEach((claim) => {
        const row = document.createElement("tr")
        row.innerHTML = `
          <td>${claim.full_name}</td>
          <td>RM ${Number.parseFloat(claim.amount).toFixed(2)}</td>
          <td>${new Date(claim.submitted_date).toLocaleDateString()}</td>
          <td>
            <span class="badge badge-${claim.status === "Approved" ? "success" : claim.status === "Rejected" ? "danger" : "warning"}">
              ${claim.status}
            </span>
          </td>
        `
        row.style.cursor = "pointer"
        row.addEventListener("click", () => {
          window.location.href = `modules/claims/view.php?id=${claim.claim_id}`
        })
        tableBody.appendChild(row)
      })
    })
}

// Export chart as image
function exportChart(chartId) {
  if (typeof ApexCharts !== "undefined") {
    ApexCharts.exec(chartId, "dataURI").then(({ imgURI }) => {
      const downloadLink = document.createElement("a")
      downloadLink.href = imgURI
      downloadLink.download = `${chartId}.png`
      downloadLink.click()
    })
  }
}
