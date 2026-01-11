document.addEventListener("click", function(e){
    const delBtn = e.target.closest(".paymentDeleteBtn");
    if(delBtn){
        const id = delBtn.dataset.id;
        if(confirm("Are you sure you want to delete this payment?")){
            window.location = "delete.php?id=" + id;
        }
    }
});
