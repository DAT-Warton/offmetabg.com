function toggleMySQLFields() {
    var driver = document.getElementById('driver').value;
    var mysqlFields = document.getElementById('mysql-fields');
    var dbTypeLabel = document.getElementById('db-type-label');
    var portField = document.getElementById('db_port');

    if (driver === 'mysql') {
        mysqlFields.style.display = 'block';
        dbTypeLabel.textContent = 'MySQL';
        portField.placeholder = '3306';
        if (!portField.value || portField.value === '5432') {
            portField.value = '3306';
        }
    } else if (driver === 'pgsql') {
        mysqlFields.style.display = 'block';
        dbTypeLabel.textContent = 'PostgreSQL';
        portField.placeholder = '5432';
        if (!portField.value || portField.value === '3306') {
            portField.value = '5432';
        }
    } else {
        mysqlFields.style.display = 'none';
    }
}
