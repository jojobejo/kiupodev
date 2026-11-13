<option value="">-- Pilih Template Shipment To --</option>
<?php foreach ($ntformat as $template) : ?>
    <option value="<?= html_escape($template->kd_nt_template) ?>" <?= $template->kd_nt_template === $shipment_code ? 'selected' : '' ?>>
        <?= html_escape($template->nama_note) ?> — <?= html_escape($template->shipment_to) ?>
    </option>
<?php endforeach; ?>
