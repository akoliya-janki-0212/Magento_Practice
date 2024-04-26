class MyGrid {
    constructor(gridElement, gridOptions) {
        this.injectStyles(); // Inject styles at the beginning
        this.data = gridOptions.rowData;
        this.originalData = [...this.data]; // Ensure original data is stored for filtering
        this.columnDefs = gridOptions.columnDefs;
        this.container = gridElement;
        this.rowHeight = 30; // Default row height
        this.totalRows = this.data.length;
        this.bufferMultiplier = this.totalRows / 30; // Number of screens worth of buffer rows

        // Create and style the table
        // Set up the container with explicit height and overflow properties
        const containerHeight = this.calculateContainerHeight();
        this.container.style.height = `${containerHeight}px`;
        // this.container.style.height = "300px";
        this.container.style.overflowY = "auto"; // Enables vertical scrolling
        this.container.style.position = "relative"; // Positioning context for scrolling and children positioning

        //
        this.table = document.createElement("table");
        this.table.style.cssText = "width: 100%; border-collapse: collapse;";
        this.thead = document.createElement("thead");
        this.tbody = document.createElement("tbody");
        this.table.appendChild(this.thead);
        this.table.appendChild(this.tbody);
        this.container.appendChild(this.table);

        this.currentSort = { column: null, direction: "" };
        this.filters = {}; // Initialize filters
        this.createHeaders();
        this.initVirtualScroll();
        this.addCheckboxListeners(); // Set up the checkbox listeners after the grid is created
        //   this.setColumnVisibilityOnLoad();
    }


    calculateContainerHeight() {
        const scrollbarWidth = this.getScrollbarWidth();
        const totalHeight = this.totalRows * this.rowHeight;
        const totalHeightWithScroll = totalHeight + scrollbarWidth;
        return Math.min(totalHeightWithScroll, window.innerHeight);
    }

    getScrollbarWidth() {
        // Create a dummy element to measure scrollbar width
        const outer = document.createElement("div");
        outer.style.visibility = "hidden";
        outer.style.width = "100px";
        outer.style.overflow = "scroll";
        document.body.appendChild(outer);

        // Inner div to force scrollbar
        const inner = document.createElement("div");
        inner.style.width = "100%";
        outer.appendChild(inner);

        // Calculate scrollbar width
        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;

        // Clean up
        outer.parentNode.removeChild(outer);

        return scrollbarWidth;
    }

    createHeaders() {
        // Generates the column headers based on the provided columnDefs
        const filterRow = document.createElement("tr");
        const headerRow = document.createElement("tr");
        this.columnDefs.forEach((colDef, index) => {
            const th = document.createElement("th");
            th.innerHTML = `${colDef.headerName} <span class='sort-icon'>&#x25B2;</span>`; // Down arrow as default

            th.style.minWidth = `${colDef.minWidth || 100}px`; // Default min-width
            th.setAttribute("draggable", true);
            th.addEventListener("dragstart", this.handleDragStart.bind(this, index));
            th.addEventListener("dragover", this.handleDragOver.bind(this, index));
            th.addEventListener("drop", this.handleDrop.bind(this, index));
            th.addEventListener("click", () => this.handleSort(colDef.field));

            //filter
            const input = document.createElement("input");
            input.type = "text";
            input.placeholder = "Filter...";
            input.oninput = (event) =>
                this.handleFilter(colDef.field, event.target.value);
            input.style.width = "100%";
            filterRow.appendChild(document.createElement("th")).appendChild(input);
            headerRow.appendChild(th);
        });
        this.thead.appendChild(headerRow);
        this.thead.appendChild(filterRow);
    }

    initVirtualScroll() {
        //sets up virtual scrolling for efficient rendering
        this.container.style.height = "400px";
        this.container.style.overflowY = "scroll";
        this.container.style.position = "relative";

        // Calculate table height based on the total number of rows and row height
        // const tableHeight = this.totalRows * this.rowHeight;

        // Set the table height to ensure scrollbar functionality
        // this.table.style.height = `${tableHeight}px`;



        this.buffer = window.innerHeight * this.bufferMultiplier; // Calculate the buffer size based on the viewport height and the number of rows
        this.visibleRows = Math.ceil(this.buffer / this.rowHeight); //visible rows based on the buffer size and row height
        this.attachEvents();
        this.renderGrid(0);
    }
    attachEvents() {
        // Use for reset the StartIndex
        let lastKnownScrollPosition = 0;
        let ticking = false;

        this.container.addEventListener("scroll", () => {
            lastKnownScrollPosition = this.container.scrollTop;
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const startIndex = Math.floor(
                        lastKnownScrollPosition / this.rowHeight
                    );
                    if (startIndex + this.visibleRows <= this.totalRows) {
                        this.renderGrid(startIndex);
                    }
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    renderGrid(startIndex) {
        const endIndex = Math.min(startIndex + this.visibleRows, this.totalRows);
        if (startIndex >= this.totalRows) {
            return; // Stop further rendering if startIndex exceeds or matches total rows count
        }
        this.tbody.innerHTML = "";

        for (let i = startIndex; i < endIndex; i++) {
            const rowElement = this.createRowElement(this.data[i], i);
            this.tbody.appendChild(rowElement);
        }
    }

    createRowElement(rowData, index) {
        const tr = document.createElement("tr");
        // Set a unique ID for each row element using the row index
        tr.id = `row-${index}`;

        tr.style.height = `${this.rowHeight}px`; // Set the row height
        this.columnDefs.forEach((colDef) => {
            if (colDef.visible !== false) {
                // Check if column is visible
                const td = document.createElement("td");
                td.textContent = rowData[colDef.field];
                tr.appendChild(td);
            }
        });
        return tr;
    }

    handleDragStart(originalIndex, event) {
        event.dataTransfer.setData("text/plain", originalIndex);
    }

    handleDragOver(targetIndex, event) {
        event.preventDefault(); // Necessary to allow dropping
    }

    handleDrop(targetIndex, event) {
        event.preventDefault();
        const originalIndex = parseInt(
            event.dataTransfer.getData("text/plain"),
            10
        );
        if (originalIndex === targetIndex) return;

        // Reorder column definitions
        const movedColumn = this.columnDefs.splice(originalIndex, 1)[0];
        this.columnDefs.splice(targetIndex, 0, movedColumn);

        // Re-render headers and grid
        this.thead.innerHTML = ""; // Clear header row
        this.createHeaders(); // Recreate headers
        this.renderGrid(0); // Recreate grid rows
        this.addCheckboxListeners();
    }

    handleSort(field) {
        const direction =
            this.currentSort.column === field && this.currentSort.direction === "asc"
                ? "desc"
                : "asc";
        this.originalData.sort((a, b) => {
            if (a[field] < b[field]) return direction === "asc" ? -1 : 1;
            if (a[field] > b[field]) return direction === "asc" ? 1 : -1;
            return 0;
        });
        this.currentSort = { column: field, direction };
        this.applyFilters(); // Apply filters after sorting to ensure data integrity
        this.updateSortIndicator(); // Update the sort indicators across all headers
    }
    updateSortIndicator() {
        // First, reset all sort icons to indicate an unsorted state
        Array.from(this.thead.querySelectorAll(".sort-icon")).forEach((icon) => {
            icon.innerHTML = "&#x25B2;"; // Use &#x25B2; or any other preferred icon for unsorted state
        });

        // Identify the column that has been sorted and update its sort icon accordingly
        const index = this.columnDefs.findIndex(
            (colDef) => colDef.field === this.currentSort.column
        );
        if (index !== -1) {
            // Ensure the column is found
            const header = this.thead.children[0].children[index]; // Access the specific header cell
            if (header) {
                const icon = header.querySelector(".sort-icon");
                if (icon) {
                    icon.innerHTML =
                        this.currentSort.direction === "asc" ? "&#x25BC;" : "&#x25B2;"; // Use &#x25B2; for ascending and &#x25BC; for descending
                }
            }
        }
    }

    handleFilter(field, value) {
        this.filters[field] = value.toLowerCase(); // Store filter value
        this.applyFilters();
    }

    applyFilters() {
        this.data = this.originalData.filter((item) => {
            return Object.keys(this.filters).every((field) => {
                // Check if item[field] is null or undefined before calling toString()
                if (item[field] == null) return this.filters[field] === ""; // If the field is null or undefined, only match if filter is empty
                return (
                    this.filters[field] === "" ||
                    item[field].toString().toLowerCase().includes(this.filters[field])
                );
            });
        });
        this.renderGrid(0);
    }
    addCheckboxListeners() {
        const checkboxes = document.querySelectorAll(
            'input[type="checkbox"][data-column]'
        );
        checkboxes.forEach((checkbox) => {
            const column = checkbox.getAttribute("data-column");
            const isChecked = checkbox.checked; // Check if the checkbox is checked
            this.setColumnVisibility(column, isChecked); // Hide the column if checked
            checkbox.addEventListener("change", (event) => {
                const isChecked = event.target.checked;
                this.setColumnVisibility(column, isChecked); // Invert visibility when checkbox is changed
            });
        });
    }

    setColumnVisibility(columnName, isVisible) {
        // Find the index of the column from the column definitions
        const columnIndex = this.columnDefs.findIndex(
            (col) => col.field === columnName
        );
        if (columnIndex === -1) return; // If no column is found, exit the function

        // Update visibility in the columnDefs array (if you use this information elsewhere)
        this.columnDefs[columnIndex].visible = isVisible;

        // Select all header and body cells in this column
        const selector = `th:nth-child(${columnIndex + 1}), td:nth-child(${columnIndex + 1
            })`;
        const cells = this.table.querySelectorAll(selector);

        // Toggle display property based on isVisible flag
        cells.forEach((cell) => {
            cell.style.display = isVisible ? "" : "none";
        });

        this.renderGrid(0);
    }

    injectStyles() {
        const style = document.createElement("style");
        style.type = "text/css";
        style.innerHTML = `
            table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      font-family: Arial, sans-serif;
  }
  
  th, td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
  }
  
  th {
      background-color: #f2f2f2;
      cursor: grab;
      position: sticky;
      top: 0;
      z-index: 2;
  }
  
  th:active {
      cursor: grabbing;
  }
  
  th:hover {
      background-color: #e0e0e0;
  }
  
  th .sort-icon {
      visibility: visible;
  }
  
  .sort-icon {
      margin-left: 5px;
      visibility: hidden;
  }
  
  td:hover {
      background-color: #f9f9f9;
  }
  
  input[type="text"] {
      box-sizing: border-box;
      padding: 8px;
      margin: 2px 0;
      border: 1px solid #ccc;
      width: 100%;
      transition: border-color 0.3s;
  }
  
  input[type="text"]:focus {
      outline: none;
      border-color: #4CAF50;
  }
  thead{
      position: sticky;
      top: 0;
      background-color: #f2f2f2;
      z-index: 1;
    }
        `;
        document.head.appendChild(style);
    }
    setRowData(newData) {
        this.data = newData;
        this.originalData = [...this.data];
        this.renderGrid(0);
    }

    getRowData() {
        return this.data;
    }
}
