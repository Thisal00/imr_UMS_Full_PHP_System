<?php
// just the modal HTML, no PHP logic needed here
?>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editForm">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-1"></i> Edit Customer
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <input type="hidden" id="edit_id" name="id">

          <div class="mb-2">
            <label class="form-label">Customer Code</label>
            <input type="text" id="edit_code" name="customer_code" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Full Name</label>
            <input type="text" id="edit_name" name="full_name" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Type</label>
            <select id="edit_type" name="type" class="form-select">
              <option>Household</option>
              <option>Business</option>
              <option>Government</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Phone</label>
            <input type="text" id="edit_phone" name="phone" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" id="edit_email" name="email" class="form-control">
          </div>

        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">
            <i class="bi bi-save2 me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
