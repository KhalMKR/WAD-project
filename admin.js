function deleteProduct(id) {
    const element = document.getElementById(`product-${id}`);
    if (element) {
        element.remove();
        console.log("Product " + id + " deleted.");
    }
}