// src/app/features/products/products.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ProductsService, Product, Category } from '../../core/services/products.service';

// import { SupabaseService } from '../../core/services/supabase.service'; // Service pour upload fichiers

@Component({
  selector: 'app-products',
  templateUrl: './products.component.html',
  styleUrls: ['./products.component.scss'],
  standalone: true,
  imports: [CommonModule, FormsModule],
})
export class ProductsComponent implements OnInit {
  // Produit en cours de création ou édition
  uploading = false;
  products: Product[] = [];
  categories: Category[] = [];  // <-- ajouter ceci
  loading = false;
  selectedCategoryId: number | null = null;
  newProduct: Partial<Product> = {};
  editingProduct: Product | null = null;
  newProductName = '';
  newProductPrice: number | null = null;
  newProductStock: number | null = null;

  constructor(
    private productsService: ProductsService,
    // private supabase: SupabaseService
  ) {}


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
  }

  cancelEdit() {
    this.editingProduct = null;
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

  getCategoryName(product: Product): string {
    const cat = this.categories.find(c => c.id === product.category_id);
    return cat ? cat.name : '—';
  }

  async saveProduct() {
  if (!this.editingProduct) return;

  // if ((this.editingProduct as any).image_file) {
  //   const url = await this.supabase.uploadFile((this.editingProduct as any).image_file);
  //   this.editingProduct.image_url = url;
  // }

  this.productsService.updateProduct(this.editingProduct.id, this.editingProduct)
    .subscribe({
      next: () => {
        const idx = this.products.findIndex(p => p.id === this.editingProduct!.id);
        if (idx > -1) this.products[idx] = { ...this.editingProduct! };
        this.editingProduct = null;
      },
      error: console.error
    });
}


// Filtrage des produits par catégorie
filteredProducts(): Product[] {
  if (!this.selectedCategoryId) return this.products;
  return this.products.filter(p => p.category_id === this.selectedCategoryId);
}

// ---------------- CRUD ----------------

  async createProduct() {
  if (!this.newProduct.name || !this.newProduct.category_id || !this.newProduct.price) {
    alert("Veuillez remplir les champs obligatoires !");
    return;
  }

  this.uploading = true;

  // Upload image si fournie
  // if (this.newProduct.image_file) {
  //   const url = await this.supabase.uploadFile(this.newProduct.image_file);
  //   this.newProduct.image_url = url;
  // }

  this.productsService.createProduct(this.newProduct as Product).subscribe({
    next: res => {
      this.products.push(res);
      this.newProduct = {};
      this.uploading = false;
    },
    error: err => {
      console.error(err);
      this.uploading = false;
    }
  });
}



  // ---------------- Helper ----------------

  // onFileSelected(event: any, target: 'new' | 'edit') {
  //   const file = event.target.files[0];
  //   if (!file) return;
  //   if (target === 'new') this.newProduct.image_file = file;
  //   else if (this.editingProduct) (this.editingProduct as any).image_file = file;
  // }

}