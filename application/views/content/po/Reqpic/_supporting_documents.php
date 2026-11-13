<?php $documents = isset($supportingDocuments) ? $supportingDocuments : array(); ?>
<div class="card mt-4">
    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-paperclip"></i> Dokumen Pendukung</h5></div>
    <div class="card-body">
        <?php if (empty($documents)) : ?>
            <p class="text-muted mb-0">Belum ada dokumen pendukung pada request ini.</p>
        <?php else : ?>
            <div class="row">
                <?php foreach ($documents as $document) : ?>
                    <?php
                    $mime = (string) $document->mime_type;
                    $url = base_url('reqpic/document/' . $document->id_supporting_file);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-truncate font-weight-bold mb-2" title="<?= htmlspecialchars($document->file_original, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($document->file_original, ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted d-block mb-2"><?= htmlspecialchars($mime, ENT_QUOTES, 'UTF-8') ?> · <?= number_format($document->file_size_bytes / 1024, 1) ?> KB</small>
                            <?php if (strpos($mime, 'image/') === 0) : ?>
                                <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer"><img src="<?= $url ?>" alt="Preview <?= htmlspecialchars($document->file_original, ENT_QUOTES, 'UTF-8') ?>" class="img-fluid border" style="max-height:190px;width:100%;object-fit:contain;"></a>
                            <?php elseif ($mime === 'application/pdf' || $mime === 'text/plain') : ?>
                                <iframe src="<?= $url ?>" title="Preview <?= htmlspecialchars($document->file_original, ENT_QUOTES, 'UTF-8') ?>" class="w-100 border" style="height:190px;"></iframe>
                            <?php else : ?>
                                <div class="d-flex align-items-center justify-content-center border bg-light text-muted" style="height:190px;"><i class="fas fa-file-excel fa-3x"></i></div>
                            <?php endif; ?>
                            <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm btn-block mt-2"><i class="fas fa-external-link-alt"></i> Buka di tab baru</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
