<?php
/**
 * Customer Management Section
 */
$customers = get_customers_data();
$action = $_GET['action'] ?? '';
$editId = $_GET['edit'] ?? null;
$editCustomer = $editId ? ($customers[$editId] ?? null) : null;
?>

<div class="section-header">
    <h2><?php echo icon_users(24); ?> <?php echo __('users.customer_management'); ?></h2>
    <a href="?section=users&action=new"class="btn"><?php echo icon_user(18); ?> <?php echo __('users.new_customer'); ?></a>
</div>

    <?php if ($editCustomer || $action === 'new'): ?>
        <div class="card">
        <form method="POST">
            <input type="hidden"name="action"value="save_customer">
            <input type="hidden"name="customer_id"value="<?php echo htmlspecialchars($editId ?? ''); ?>">

            <div class="form-group">
                <label><?php echo __('users.username'); ?></label>
                <input type="text"name="username"value="<?php echo htmlspecialchars($editCustomer['username'] ?? ''); ?>"required>
            </div>

            <div class="form-group">
                <label><?php echo __('users.email'); ?></label>
                <input type="email"name="email"value="<?php echo htmlspecialchars($editCustomer['email'] ?? ''); ?>"required>
            </div>

            <div class="form-group">
                <label><?php echo __('auth.password'); ?> <?php echo $editCustomer ? '(оставете празно за да запазите текущата)' : ''; ?></label>
                <input type="password"name="password"<?php echo !$editCustomer ? 'required' : ''; ?> minlength="6">
                <small class="hint">Минимум 6 символа</small>
            </div>

            <div class="form-group">
                <label><?php echo __('users.role'); ?> / Ниво на достъп</label>
                <select name="role"required>
                    <option value="customer"<?php echo ($editCustomer['role'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>><?php echo __('users.customer'); ?> (Стандартен потребител)</option>
                    <option value="employee"<?php echo ($editCustomer['role'] ?? '') === 'employee' ? 'selected' : ''; ?>><?php echo __('users.employee'); ?> (Преглед на поръчки, продукти)</option>
                    <option value="manager"<?php echo ($editCustomer['role'] ?? '') === 'manager' ? 'selected' : ''; ?>><?php echo __('users.manager'); ?> (Управление на поръчки, инвентар)</option>
                    <option value="admin"<?php echo ($editCustomer['role'] ?? '') === 'admin' ? 'selected' : ''; ?>><?php echo __('users.admin'); ?> (Пълен достъп)</option>
                </select>
                <small>
                    <strong><?php echo __('users.customer'); ?>:</strong> Може да пазарува<br>
                    <strong><?php echo __('users.employee'); ?>:</strong> Може да вижда поръчки и продукти<br>
                    <strong><?php echo __('users.manager'); ?>:</strong> Може да управлява поръчки и инвентар<br>
                    <strong><?php echo __('users.admin'); ?>:</strong> Пълен достъп до системата
                </small>
            </div>

            <div class="form-actions">
                <button type="submit"class="btn"><?php echo icon_check(18); ?> <?php echo $editCustomer ? __('update') : __('create'); ?> <?php echo __('users.customer'); ?></button>
                <a href="?section=users"class="btn-secondary"><?php echo icon_x(18); ?> <?php echo __('cancel'); ?></a>
            </div>
        </form>
        </div>
    <?php endif; ?>

    <div class="card">
    <table>
        <thead>
            <tr>
                <th><?php echo __('users.username'); ?></th>
                <th><?php echo __('users.email'); ?></th>
                <th><?php echo __('users.role'); ?></th>
                <th><?php echo __('users.created'); ?></th>
                <th><?php echo __('users.actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5"class="table-empty">
                        <?php echo icon_user(32); ?><br>
                        Все още няма клиенти. Натиснете "<?php echo __('users.new_customer'); ?>", за да добавите!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $id => $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td>
                            <?php 
                                $role = $customer['role'] ?? 'customer';
                                $roleLabels = [
                                    'admin' => 'Admin',
                                    'manager' => 'Manager',
                                    'employee' => 'Employee',
                                    'customer' => 'Customer'
                                ];
                                $roleLabel = $roleLabels[$role] ?? $roleLabels['customer'];
                            ?>
                            <span class="role-badge role-<?php echo htmlspecialchars($role); ?>">
                                <?php echo $roleLabel; ?>
                            </span>
                        </td>
                        <td><?php echo $customer['created'] ?? 'N/A'; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="?section=users&edit=<?php echo $id; ?>"class="btn-small"><?php echo icon_edit(14); ?> <?php echo __('edit'); ?></a>
                                <form method="POST"class="inline-form">
                                    <input type="hidden"name="action"value="delete_customer">
                                    <input type="hidden"name="customer_id"value="<?php echo $id; ?>">
                                    <button type="submit"class="btn-delete"onclick="return confirm('Изтрий този клиент? Действието не може да бъде отменено.')"><?php echo icon_trash(14); ?> <?php echo __('delete'); ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

