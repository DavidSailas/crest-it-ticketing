# IT Supplies module — install

1. Copy everything in this zip into your project (same folders). The only existing
   file it replaces is `resources/views/layouts/navigation.blade.php` (adds the
   Supplies link + restock badge).

2. In `routes/web.php`, add ONE line just above `require __DIR__.'/auth.php';`:

       require __DIR__.'/supplies.php';

3. Create the tables:

       php artisan migrate

4. Rebuild the CSS (the new pages use a few Tailwind classes your current build doesn't have yet):

       npm run build

5. Optional sample data (ink, batteries, mouse, keyboard, USB…):

       php artisan db:seed --class=SupplySeeder

6. Optional: run the tests (needs your MySQL test DB — your older migrations use MySQL-only syntax):

       php artisan test --filter=SupplyTest

Permissions
  Staff       view list + item history
  IT Support  view, add, edit, stock in / stock out / set count
  Admin       everything, plus delete
