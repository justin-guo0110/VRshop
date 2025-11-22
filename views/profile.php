<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser): ?>
    <div class="card">
        <p>Please <a href="login.php">login</a> to manage your profile.</p>
    </div>
<?php else: ?>
    <section class="grid two-cols">
        <div class="card">
            <h2>Profile</h2>
            <form id="profileForm">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" required>
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                <button type="submit" class="btn">Update Profile</button>
            </form>
            <div class="message" id="profileMessage"></div>
        </div>
        <div class="card">
            <h2>Addresses</h2>
            <div id="addressList"></div>
            <h3>Add Address</h3>
            <form id="addressForm">
                <label>Recipient Name</label>
                <input type="text" name="recipient_name" required>
                <label>Phone</label>
                <input type="text" name="phone">
                <label>Address</label>
                <input type="text" name="address_line" required>
                <label class="checkbox">
                    <input type="checkbox" name="is_default" value="1"> Set as default
                </label>
                <button type="submit" class="btn">Save Address</button>
            </form>
            <div class="message" id="addressMessage"></div>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
