from django.core.management.base import BaseCommand
from core.models import Category


DEFAULTS = {
    'income': ['Allowance', 'Part-time Job', 'Scholarship', 'Gift', 'Other Income'],
    'expense': ['Food', 'Transport', 'Hostel/Rent', 'Academics', 'Subscriptions', 'Entertainment', 'Miscellaneous'],
}


class Command(BaseCommand):
    help = 'Create the default Campus Coin income and expense categories.'

    def handle(self, *args, **options):
        count = 0
        for category_type, names in DEFAULTS.items():
            for name in names:
                _, created = Category.objects.get_or_create(name=name, type=category_type, user=None, defaults={'is_default': True})
                count += created
        self.stdout.write(self.style.SUCCESS(f'{count} default categories created.'))
