function addcategory() {
    const newStaff = {
        id: prompt("Enter ID:"),
        name: prompt("Enter Name:"),
        role: prompt("Enter Role:"),
        phone: prompt("Enter Phone:"),
        email: prompt("Enter Email:")
    };

    fetch("add_staff.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newStaff)
    }).then(res => res.json()).then(data => {
        alert(data.success ? "Staff added!" : "Failed to add staff");
        if (data.success) location.reload();
    });
}

function deletecategory() {
    const id = prompt("Enter ID to delete:");
    fetch("delete_staff.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
    }).then(res => res.json()).then(data => {
        alert(data.success ? "Deleted!" : "Failed to delete");
        if (data.success) location.reload();
    });
}

function editstaff() {
    const id = prompt("Enter ID to edit:");
    const updated = {
        id,
        name: prompt("New Name:"),
        role: prompt("New Role:"),
        phone: prompt("New Phone:"),
        email: prompt("New Email:")
    };

    fetch("edit_staff.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updated)
    }).then(res => res.json()).then(data => {
        alert(data.success ? "Updated!" : "Failed to update");
        if (data.success) location.reload();
    });
}

function viewstaff() {
    fetch("view_staff.php")
        .then(res => res.json())
        .then(data => {
            let tableHTML = '';
            data.forEach(staff => {
                tableHTML += `<tr>
                    <td>${staff.id}</td>
                    <td>${staff.name}</td>
                    <td>${staff.role}</td>
                    <td>${staff.phone}</td>
                    <td>${staff.email}</td>
                </tr>`;
            });
            document.querySelector("tbody").innerHTML = tableHTML;
        });
}
