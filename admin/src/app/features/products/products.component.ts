// src/app/features/products/products.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ProductsService, Product, Category } from '../../core/services/products.service';

@Component({
  selector: 'app-products',
  templateUrl: './products.component.html',
  styleUrls: ['./products.component.scss'],
  standalone: true,
  imports: [CommonModule, FormsModule],
})
export class ProductsComponent implements OnInit {
  products: Product[] = [];
  categories: Category[] = [];
  selectedCategoryId: number | null = null;
  newProduct: Partial<Product> = {};
  editingProduct: Product | null = null;
  newProductName = '';
  newProductPrice: number | null = null;
  newProductStock: number | null = null;
  uploading = false;
  loading = false;
  showCreateForm = false;
  showEditForm = false;
  showCategoryForm = false;
  newCategory: Partial<Category> = {
    name: '',
    description: '',
    image_url: ''
  };

  constructor(
    private productsService: ProductsService,
  ) { }


  ngOnInit(): void {
    this.loadProducts();
    this.loadCategories();
  }

  loadProducts(): void {
    this.loading = true;
    this.productsService.getProducts().subscribe({
      next: res => {
        this.products = res.data;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  startEdit(product: Product) {
    this.editingProduct = { ...product };
    this.showEditForm = true;
  }


  cancelEdit() {
    this.editingProduct = null;
    this.showEditForm = false;
  }


  saveEdit() {
    if (!this.editingProduct) return;
    this.productsService.updateProduct(this.editingProduct.id, this.editingProduct)
      .subscribe({
        next: () => {
          this.loadProducts();
          this.editingProduct = null;
        },
        error: console.error,
      });
  }


  deleteProduct(product: Product) {
    if (!confirm(`Supprimer le produit "${product.name}" ?`)) return;

    this.productsService.deleteProduct(product.id).subscribe(() => {
      this.products = this.products.filter(p => p.id !== product.id);
    });
  }

  addProduct() {
    if (!this.newProductName || this.newProductPrice == null || this.newProductStock == null) return;
    this.productsService.createProduct({
      name: this.newProductName,
      price: this.newProductPrice,
      quantity: this.newProductStock,
    }).subscribe({
      next: () => {
        this.loadProducts();
        this.newProductName = '';
        this.newProductPrice = null;
        this.newProductStock = null;
      },
      error: console.error,
    });
  }

  updateStock(product: Product, stock: number) {
    this.productsService.updateStock(product.id, stock).subscribe({
      next: () => this.loadProducts(),
      error: console.error,
    });
  }

  private loadCategories() {
    this.productsService.getCategories().subscribe(res => (this.categories = res.data));
  }

  /**
 * Get category name - accepts Product object OR category ID
 */
  getCategoryName(productOrId: Product | number): string {
    let categoryId: number;

    if (typeof productOrId === 'number') {
      categoryId = productOrId;
    } else {
      categoryId = productOrId.category_id;
    }

    const category = this.categories.find(c => c.id === categoryId);
    return category ? category.name : 'Sans catégorie';
  }

  async saveProduct() {
    if (!this.editingProduct) return;

    this.productsService
      .updateProduct(this.editingProduct.id, this.editingProduct)
      .subscribe({
        next: () => {
          this.loadProducts();
          this.cancelEdit();
        },
        error: console.error,
      });
  }



  // Filtrage des produits par catégorie
  filteredProducts(): Product[] {
    if (!this.selectedCategoryId) return this.products;
    return this.products.filter(p => p.category_id === this.selectedCategoryId);
  }

  openCreateForm() {
    this.newProduct = {
      is_active: true
    };
    this.showCreateForm = true;
  }

  cancelCreate() {
    this.newProduct = {};
    this.showCreateForm = false;
  }

  createProduct() {
    if (
      !this.newProduct.name ||
      !this.newProduct.price ||
      !this.newProduct.quantity ||
      !this.newProduct.category_id
    ) {
      alert('Tous les champs obligatoires doivent être remplis');
      return;
    }

    this.productsService.createProduct(this.newProduct).subscribe({
      next: () => {
        this.loadProducts();
        this.cancelCreate();
      },
      error: console.error,
    });
  }

  countProductsByCategory(categoryId: number): number {
    return this.products.filter(p => p.category_id === categoryId).length;
  }

  // onImageError(event: Event) {
  //   const img = event.target as HTMLImageElement;
  //   if (img) {
  //     img.src = this.placeholderImage;
  //   }
  // }


  /**
   * Cancel both create and edit forms (universal close)
   */
  cancelAll(): void {
    if (this.showCreateForm) {
      this.cancelCreate();
    }
    if (this.showEditForm) {
      this.cancelEdit();
    }
    if (this.showCategoryForm) {
      this.showCategoryForm = false;
      this.resetCategoryForm();
    }
  }

  /**
 * Validate product data (for both create and edit)
 */
  isValid(product: Partial<Product> | null): boolean {
    if (!product) return false;

    return !!(
      product.name?.trim() &&
      product.price !== null &&
      product.price !== undefined &&
      product.price > 0 &&
      product.quantity !== null &&
      product.quantity !== undefined &&
      product.quantity >= 0 &&
      product.category_id
    );
  }

  /**
 * Crée une nouvelle catégorie
 */
  createCategory() {
    if (!this.newCategory.name) return;

    this.productsService.createCategory(this.newCategory).subscribe({
      next: (res) => {
        // Recharger les catégories pour mettre à jour la liste défilable
        this.loadCategories();
        // Réinitialiser et fermer
        this.resetCategoryForm();
        this.showCategoryForm = false;
      },
      error: (err) => {
        console.error('Erreur lors de la création de la catégorie', err);
        alert('Erreur lors de la création de la catégorie');
      }
    });
  }

  /**
   * Réinitialise l'objet catégorie
   */
  resetCategoryForm() {
    this.newCategory = {
      name: '',
      description: '',
      image_url: ''
    };
  }
}