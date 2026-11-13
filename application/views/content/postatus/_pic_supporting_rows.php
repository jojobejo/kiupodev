<?php foreach ((isset($documents) ? $documents : array()) as $document) : ?>
    <tr class="table-info">
        <td colspan="<?= (int) $colspan ?>">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mr-3">
                    <span class="badge badge-info mr-2">PIC</span>
                    <strong><?= htmlspecialchars($document->file_original, ENT_QUOTES, 'UTF-8') ?></strong>
                    <small class="text-muted ml-2"><?= htmlspecialchars($document->mime_type, ENT_QUOTES, 'UTF-8') ?> · <?= number_format($document->file_size_bytes / 1024, 1) ?> KB</small>
                </div>
                <a class="btn btn-primary btn-sm mt-1 mt-md-0" target="_blank" rel="noopener noreferrer" href="<?= base_url('reqpic/document/' . $document->id_supporting_file) ?>"><i class="fas fa-external-link-alt"></i> Buka</a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
