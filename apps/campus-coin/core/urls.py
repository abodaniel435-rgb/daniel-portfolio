from django.urls import path
from .views import AdminAnalyticsView, AnnouncementListView, BudgetListCreateView, BudgetDetailView, CategoryDetailView, CategoryListCreateView, CsvImportView, DashboardView, GoalDetailView, GoalListCreateView, HeatmapView, InsightDetailView, InsightListView, MonthlyReportView, PulseView, RoommateView, SplitDetailView, SplitListCreateView, SplitSettleView, TipGenerationView, TransactionDetailView, TransactionListCreateView

urlpatterns = [
    path('dashboard/', DashboardView.as_view(), name='dashboard'),
    path('pulse/', PulseView.as_view(), name='pulse'),
    path('roommate/', RoommateView.as_view(), name='roommate'),
    path('goals/', GoalListCreateView.as_view(), name='goals'),
    path('goals/<int:pk>/', GoalDetailView.as_view(), name='goal-detail'),
    path('heatmap/', HeatmapView.as_view(), name='heatmap'),
    path('splits/', SplitListCreateView.as_view(), name='splits'),
    path('splits/<int:pk>/', SplitDetailView.as_view(), name='split-detail'),
    path('splits/<int:pk>/members/<int:member_id>/settle/', SplitSettleView.as_view(), name='split-settle'),
    path('categories/', CategoryListCreateView.as_view(), name='categories'),
    path('categories/<int:pk>/', CategoryDetailView.as_view(), name='category-detail'),
    path('transactions/', TransactionListCreateView.as_view(), name='transactions'),
    path('transactions/<int:pk>/', TransactionDetailView.as_view(), name='transaction-detail'),
    path('budgets/', BudgetListCreateView.as_view(), name='budgets'),
    path('budgets/<int:pk>/', BudgetDetailView.as_view(), name='budget-detail'),
    path('insights/', InsightListView.as_view(), name='insights'),
    path('insights/<int:pk>/', InsightDetailView.as_view(), name='insight-detail'),
    path('announcements/', AnnouncementListView.as_view(), name='announcements'),
    path('reports/monthly/', MonthlyReportView.as_view(), name='monthly-report'),
    path('transactions/import-csv/', CsvImportView.as_view(), name='csv-import'),
    path('insights/generate/', TipGenerationView.as_view(), name='generate-tips'),
    path('admin/analytics/', AdminAnalyticsView.as_view(), name='admin-analytics'),
]
