class MyGrid {
    constructor(gridElement, gridOptions) {
        this.injectStyles();  // Inject styles at the beginning
        this.data = gridOptions.rowData;
        this.originalData = [...this.data]; // Ensure original data is stored for filtering
        this.columnDefs = gridOptions.columnDefs;
        this.container = gridElement;
        this.rowHeight = 30; // Default row height
        this.bufferMultiplier = 1000; // Number of screens worth of buffer rows
        this.totalRows = this.data.length;

        // Create and style the table
        this.table = document.createElement('table');
        this.table.style.cssText = 'width: 100%; border-collapse: collapse;';
        this.thead = document.createElement('thead');
        this.tbody = document.createElement('tbody');
        this.table.appendChild(this.thead);
        this.table.appendChild(this.tbody);
        this.container.appendChild(this.table);

        this.currentSort = { column: null, direction: '' };
        this.filters = {}; // Initialize filters 
        this.createHeaders();
        this.initVirtualScroll();
        this.addCheckboxListeners();  // Set up the checkbox listeners after the grid is created
    }

    createHeaders() {
        const filterRow = document.createElement('tr');
        const headerRow = document.createElement('tr');
        this.columnDefs.forEach((colDef, index) => {
            const th = document.createElement('th');
            th.innerHTML = `${colDef.headerName} <span class='sort-icon'>&#x25B2;</span>`; // Down arrow as default

            th.style.minWidth = `${colDef.minWidth || 100}px`; // Default min-width
            th.setAttribute('draggable', true);
            th.addEventListener('dragstart', this.handleDragStart.bind(this, index));
            th.addEventListener('dragover', this.handleDragOver.bind(this, index));
            th.addEventListener('drop', this.handleDrop.bind(this, index));
            th.addEventListener('click', () => this.handleSort(colDef.field));

            //filter
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Filter...';
            input.oninput = (event) => this.handleFilter(colDef.field, event.target.value);
            input.style.width = '100%';
            filterRow.appendChild(document.createElement('th')).appendChild(input);
            headerRow.appendChild(th);
        });
        this.thead.appendChild(headerRow);
        this.thead.appendChild(filterRow);
    }

    initVirtualScroll() {
        this.container.style.height = `${window.innerHeight}px`;
        this.container.style.overflowY = 'scroll';
        this.container.style.position = 'relative';
        this.buffer = window.innerHeight * this.bufferMultiplier;
        this.visibleRows = Math.ceil(this.buffer / this.rowHeight);
        this.attachEvents();
        this.renderGrid(0);
    }
    attachEvents() {
        this.container.addEventListener('scroll', () => {
            const scrollTop = this.container.scrollTop;
            const startIndex = Math.floor(scrollTop / this.rowHeight);
            this.renderGrid(startIndex);
        });
    }
    renderGrid(startIndex) {
        const endIndex = startIndex + this.visibleRows;
        this.tbody.innerHTML = ''; // Clear previous rows

        for (let i = startIndex; i < Math.min(endIndex, this.totalRows); i++) {
            const rowElement = this.createRowElement(this.data[i], i);
            this.tbody.appendChild(rowElement);
        }
    }

    createRowElement(rowData, index) {
        const tr = document.createElement('tr');
        tr.style.height = `${this.rowHeight}px`; // Set the row height
        this.columnDefs.forEach(colDef => {
            const td = document.createElement('td');
            td.textContent = rowData[colDef.field];
            tr.appendChild(td);
        });
        return tr;
    }

    handleDragStart(originalIndex, event) {
        event.dataTransfer.setData('text/plain', originalIndex);
    }

    handleDragOver(targetIndex, event) {
        event.preventDefault(); // Necessary to allow dropping
    }

    handleDrop(targetIndex, event) {
        event.preventDefault();
        const originalIndex = parseInt(event.dataTransfer.getData('text/plain'), 10);
        if (originalIndex === targetIndex) return;

        // Reorder column definitions
        const movedColumn = this.columnDefs.splice(originalIndex, 1)[0];
        this.columnDefs.splice(targetIndex, 0, movedColumn);

        // Re-render headers and grid
        this.thead.innerHTML = ''; // Clear header row
        this.createHeaders(); // Recreate headers
        this.renderGrid(); // Recreate grid rows
    }

    handleSort(field) {
        const direction = this.currentSort.column === field && this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        this.originalData.sort((a, b) => {
            if (a[field] < b[field]) return direction === 'asc' ? -1 : 1;
            if (a[field] > b[field]) return direction === 'asc' ? 1 : -1;
            return 0;
        });
        this.currentSort = { column: field, direction };
        this.applyFilters();  // Apply filters after sorting to ensure data integrity
        this.updateSortIndicator();  // Update the sort indicators across all headers
    }
    updateSortIndicator() {
        // First, reset all sort icons to indicate an unsorted state
        Array.from(this.thead.querySelectorAll('.sort-icon')).forEach(icon => {
            icon.innerHTML = '&#x25B2;'; // Use &#x25B2; or any other preferred icon for unsorted state
        });

        // Identify the column that has been sorted and update its sort icon accordingly
        const index = this.columnDefs.findIndex(colDef => colDef.field === this.currentSort.column);
        if (index !== -1) {  // Ensure the column is found
            const header = this.thead.children[0].children[index];  // Access the specific header cell
            if (header) {
                const icon = header.querySelector('.sort-icon');
                if (icon) {
                    icon.innerHTML = this.currentSort.direction === 'asc' ? '&#x25B2;' : '&#x25BC;';  // Use &#x25B2; for ascending and &#x25BC; for descending
                }
            }
        }
    }

    handleFilter(field, value) {
        this.filters[field] = value.toLowerCase(); // Store filter value
        this.applyFilters();
    }

    applyFilters() {
        this.data = this.originalData.filter(item => {
            return Object.keys(this.filters).every(field => {
                // Check if item[field] is null or undefined before calling toString()
                if (item[field] == null) return this.filters[field] === ''; // If the field is null or undefined, only match if filter is empty
                return this.filters[field] === '' || item[field].toString().toLowerCase().includes(this.filters[field]);
            });
        });
        this.renderGrid(0);
    }
    addCheckboxListeners() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-column]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (event) => {
                const column = event.target.getAttribute('data-column');
                const isChecked = event.target.checked;
                this.setColumnVisibility(column, isChecked);
            });
        });
    }
    setColumnVisibility(columnName, isVisible) {
        const columnIndex = this.columnDefs.findIndex(col => col.field === columnName);
        if (columnIndex !== -1) {
            this.columnDefs[columnIndex].visible = isVisible;  // Update visibility in the columnDefs
            const headerCells = this.thead.querySelectorAll('th');
            const bodyCells = this.tbody.querySelectorAll(`td:nth-child(${columnIndex + 1})`);

            if (headerCells[columnIndex]) {
                headerCells[columnIndex].style.display = isVisible ? '' : 'none';
            }

            bodyCells.forEach(cell => {
                cell.style.display = isVisible ? '' : 'none';
            });
        }
    }

    injectStyles() {
        const style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = `
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                cursor: grab;
            }
            th:active {
                cursor: grabbing;
            }
            /* Hide the sort icon by default */
            th .sort-icon {
                visibility: hidden;
                margin-left: 5px;  /* Adjust spacing as needed */
            }
            
            /* Show the sort icon when the header is hovered */
            th:hover .sort-icon {
                visibility: visible;
            }
            input[type="text"] {
                box-sizing: border-box;
                padding: 4px;
                margin: 2px 0;
                border: 1px solid #ccc;
            }
        `;
        document.head.appendChild(style);
    }
}
