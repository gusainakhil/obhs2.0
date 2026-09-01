(() => {
    'use strict';

    // Inline attributes in the existing table call these window functions.
    window.openEditModal = (id, name, employeeId, designation, photo) => {
        document.getElementById('edit_employee_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_employee_id_code').value = employeeId;
        document.getElementById('edit_designation').value = designation;
        document.getElementById('old_photo').value = photo;

        const photoPreview = document.getElementById('current_photo_preview');
        if (photo) {
            photoPreview.textContent = '';
            const image = document.createElement('img');
            image.src = 'uploads/employee/' + encodeURIComponent(photo);
            image.alt = 'Current Photo';
            image.className = 'w-20 h-20 object-cover rounded border';
            photoPreview.appendChild(image);
        } else {
            photoPreview.innerHTML = '<p class="text-sm text-slate-500">No photo uploaded</p>';
        }

        document.getElementById('editModal').classList.remove('hidden');
    };

    window.closeEditModal = () => {
        document.getElementById('editModal').classList.add('hidden');
    };

    window.deleteEmployee = (id) => {
        if (window.confirm('Are you sure you want to delete this employee? This will also delete their photo.')) {
            document.getElementById('delete_employee_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    };

    window.exportPDF = () => window.open('export-employee-pdf.php', '_blank');
    window.exportExcel = () => window.open('export-employee-excel.php', '_blank');

    window.changeEntries = (perPage) => {
        window.location.href = '?per_page=' + encodeURIComponent(perPage) + '&page=1';
    };

    window.searchTable = () => {
        const filter = document.getElementById('searchInput').value.toUpperCase();
        const rows = document.getElementById('employeeTableBody').getElementsByTagName('tr');

        for (const row of rows) {
            const matches = Array.from(row.getElementsByTagName('td')).some((cell) =>
                (cell.textContent || cell.innerText).toUpperCase().includes(filter)
            );
            row.style.display = matches ? '' : 'none';
        }
    };

    window.goToPage = (page) => {
        const perPage = document.getElementById('entriesPerPage').value;
        window.location.href = '?per_page=' + encodeURIComponent(perPage) + '&page=' + encodeURIComponent(page);
    };
})();
