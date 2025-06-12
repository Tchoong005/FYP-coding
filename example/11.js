
    function addcategory() {
        const data = {
            action: 'add',
            id: prompt("Staff ID:"),
            name: prompt("Staff Name:"),
            role: prompt("Staff Role:"),
            phone: prompt("Staff Phone:"),
            email: prompt("Staff Email:")
        };

        fetch('staff_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        })
        .then(res => res.text())
        .then(result => alert("Staff added: " + result));
    }

    function deletecategory() {
        const id = prompt("Enter Staff ID to delete:");
        fetch('staff_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', id })
        })
        .then(res => res.text())
        .then(result => alert("Delete result: " + result));
    }

    function editstaff() {
        const id = prompt("Staff ID to edit:");
        const name = prompt("New Name:");
        const role = prompt("New Role:");
        const phone = prompt("New Phone:");
        const email = prompt("New Email:");

        fetch('staff_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'edit', id, name, role, phone, email })
        })
        .then(res => res.text())
        .then(result => alert("Edit result: " + result));
    }

    function viewstaff() {
        const id = prompt("Staff ID to view:");
        fetch('staff_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'view', id })
        })
        .then(res => res.json())
        .then(data => {
            if (data) {
                alert(`Name: ${data.name}\nRole: ${data.role}\nPhone: ${data.phone}\nEmail: ${data.email}`);
            } else {
                alert("Staff not found.");
            }
        });
    }

