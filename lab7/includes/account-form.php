<?php
function accountInput(string $name, string $label, string $type = 'text', string $value = '', string $autocomplete = ''): void
{
    ?>
    <div class="mb-3">
        <label class="form-label" for="<?= escapeHtml($name) ?>"><?= escapeHtml($label) ?></label>
        <input class="form-control" id="<?= escapeHtml($name) ?>" name="<?= escapeHtml($name) ?>"
               type="<?= escapeHtml($type) ?>" value="<?= escapeHtml($value) ?>"
               autocomplete="<?= escapeHtml($autocomplete) ?>" required>
    </div>
    <?php
}

function showFormError(string $error): void
{
    if ($error !== '') {
        echo '<p class="alert alert-danger" role="alert">' . escapeHtml($error) . '</p>';
    }
}
