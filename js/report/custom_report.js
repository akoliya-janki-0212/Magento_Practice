
class VNode {
    constructor(tag, props, children) {
        this.tag = tag;
        this.props = props;
        this.children = children || [];
    }
}

class VDom {
    createElement(tag, props, ...children) {
        if (typeof tag !== 'string') {
            throw new Error("Tag must be a string, but got: " + typeof tag);
        }
        // Ensure all children are either VNodes or strings
        const processedChildren = children.flat().map(child =>
            child instanceof VNode ? child : String(child)
        );
        return new VNode(tag, props, processedChildren);
    }


    render(vnode, container) {
        if (typeof vnode === "string") {
            container.appendChild(document.createTextNode(vnode));
            return;
        }

        if (!vnode || typeof vnode.tag !== 'string') {
            console.error("Invalid vnode:", vnode);
            return; // Stop further execution if vnode is invalid
        }

        const element = document.createElement(vnode.tag);
        Object.entries(vnode.props || {}).forEach(([key, value]) => {
            element.setAttribute(key, value.toString());
        });

        vnode.children.forEach(child => this.render(child, element));
        container.appendChild(element);
    }
}
class MyGrid {
    constructor(jsonData, containerId) {
        this.jsonData = jsonData;
        this.container = document.getElementById(containerId);
        this.vDom = new VDom(); // Instance of Virtual DOM
    }

    createTable() {
        // Create header using Virtual DOM
        const headers = Object.keys(this.jsonData[0]);
        const headerRow = this.vDom.createElement('tr', {},
            headers.map((header, index) => this.vDom.createElement('tr', {
                draggable: "true",
                ondragstart: `event.dataTransfer.setData('type', 'column'); event.dataTransfer.setData('index', '${index}'); event.dataTransfer.effectAllowed = 'move';`,
                ondragover: "event.preventDefault()",
                ondrop: "event.preventDefault(); this.dispatchEvent(new CustomEvent('drop', {detail: {type: 'column', from: event.dataTransfer.getData('index'), to: this.cellIndex}, bubbles: true}));",
                ondragend: "event.target.style.opacity = '';" // Optional: reset any visual cues
            }, header))
        );

        // Create rows using Virtual DOM with drag and drop
        const rows = this.jsonData.map((item, rowIndex) => this.vDom.createElement('tr', {
            draggable: "true",
            ondragstart: `event.dataTransfer.setData('type', 'row'); event.dataTransfer.setData('index', '${rowIndex}'); event.dataTransfer.effectAllowed = 'move';`,
            ondragover: "event.preventDefault()",
            ondrop: "event.preventDefault(); this.dispatchEvent(new CustomEvent('drop', {detail: {type: 'row', from: event.dataTransfer.getData('index'), to: rowIndex}, bubbles: true}));",
            ondragend: "event.target.style.opacity = '';" // Optional: reset any visual cues
        }, headers.map(header => this.vDom.createElement('td', { 'data-label': header }, item[header]))
        ));

        return this.vDom.createElement('table', { style: "width: 100%", id: "myTable" }, headerRow, ...rows);
    }

    handleDrop(event) {
        const fromIndex = parseInt(event.detail.from, 10);
        const toIndex = parseInt(event.detail.to, 10);
        const type = event.detail.type;

        if (fromIndex === toIndex) return;

        if (type === 'column') {
            // Swap header and corresponding data in all rows
            this.jsonData.forEach(row => {
                let keys = Object.keys(row);
                let temp = row[keys[fromIndex]];
                row[keys[fromIndex]] = row[keys[toIndex]];
                row[keys[toIndex]] = temp;
            });
        } else if (type === 'row') {
            // Swap rows
            [this.jsonData[fromIndex], this.jsonData[toIndex]] = [this.jsonData[toIndex], this.jsonData[fromIndex]];
        }

        this.renderGrid(); // Re-render the grid with new order
    }

    renderGrid() {
        this.container.innerHTML = ''; // Clear the container
        const tableVNode = this.createTable();
        this.vDom.render(tableVNode, this.container); // Render using Virtual DOM
        this.container.querySelector('table').addEventListener('drop', this.handleDrop.bind(this));
    }
}


