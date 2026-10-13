<template>
	<component :is="iconComponent" aria-hidden="true" />
</template>

<script>
import {
	AcademicCapIcon, ArchiveBoxIcon, ArrowDownIcon, ArrowLeftIcon, ArrowRightIcon,
	ArrowUpRightIcon, Bars3Icon, BarsArrowDownIcon, BellIcon, BriefcaseIcon,
	BuildingOfficeIcon, CalendarIcon, ChatBubbleLeftRightIcon, CheckIcon,
	CheckCircleIcon, ClockIcon, CreditCardIcon, CursorArrowRaysIcon,
	DocumentArrowDownIcon, DocumentDuplicateIcon, DocumentTextIcon, EnvelopeIcon,
	ExclamationCircleIcon, ExclamationTriangleIcon, FolderIcon, FunnelIcon,
	HeartIcon, HomeIcon, IdentificationIcon, InboxArrowDownIcon, LightBulbIcon,
	LinkIcon, MagnifyingGlassIcon, MapPinIcon, NewspaperIcon, PaperClipIcon,
	PauseIcon, PencilSquareIcon, PhoneIcon, PhotoIcon, PlayIcon, PlusIcon,
	RectangleStackIcon, TrashIcon, UserIcon, UserCircleIcon, UserGroupIcon,
	XCircleIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import {
	Bars3Icon as SolidBars3Icon, BellIcon as SolidBellIcon,
	ChatBubbleLeftRightIcon as SolidChatBubbleLeftRightIcon,
	CheckCircleIcon as SolidCheckCircleIcon, ClockIcon as SolidClockIcon,
	EnvelopeIcon as SolidEnvelopeIcon, ExclamationCircleIcon as SolidExclamationCircleIcon,
	FolderIcon as SolidFolderIcon, FunnelIcon as SolidFunnelIcon,
	HeartIcon as SolidHeartIcon, MagnifyingGlassIcon as SolidMagnifyingGlassIcon,
	PaperClipIcon as SolidPaperClipIcon, PauseIcon as SolidPauseIcon,
	PencilSquareIcon as SolidPencilSquareIcon, PlayIcon as SolidPlayIcon,
	TrashIcon as SolidTrashIcon, UserIcon as SolidUserIcon,
	UserCircleIcon as SolidUserCircleIcon, XCircleIcon as SolidXCircleIcon,
	XMarkIcon as SolidXMarkIcon,
} from '@heroicons/vue/20/solid';

const outlineIcons = {
	'academic-cap': AcademicCapIcon,
	archive: ArchiveBoxIcon,
	'arrow-down': ArrowDownIcon,
	'arrow-left': ArrowLeftIcon,
	'arrow-right': ArrowRightIcon,
	'arrow-up-right': ArrowUpRightIcon,
	menu: Bars3Icon,
	'sort-descending': BarsArrowDownIcon,
	bell: BellIcon,
	briefcase: BriefcaseIcon,
	'office-building': BuildingOfficeIcon,
	calendar: CalendarIcon,
	'chat-alt-2': ChatBubbleLeftRightIcon,
	check: CheckIcon,
	'check-circle': CheckCircleIcon,
	clock: ClockIcon,
	'credit-card': CreditCardIcon,
	'cursor-click': CursorArrowRaysIcon,
	'save-as': DocumentArrowDownIcon,
	'document-duplicate': DocumentDuplicateIcon,
	'document-text': DocumentTextIcon,
	mail: EnvelopeIcon,
	'exclamation-circle': ExclamationCircleIcon,
	'exclamation-triangle': ExclamationTriangleIcon,
	folder: FolderIcon,
	filter: FunnelIcon,
	heart: HeartIcon,
	home: HomeIcon,
	identification: IdentificationIcon,
	'inbox-in': InboxArrowDownIcon,
	'light-bulb': LightBulbIcon,
	link: LinkIcon,
	search: MagnifyingGlassIcon,
	'location-marker': MapPinIcon,
	newspaper: NewspaperIcon,
	'paper-clip': PaperClipIcon,
	pause: PauseIcon,
	'pencil-alt': PencilSquareIcon,
	phone: PhoneIcon,
	photo: PhotoIcon,
	play: PlayIcon,
	plus: PlusIcon,
	collection: RectangleStackIcon,
	trash: TrashIcon,
	user: UserIcon,
	'user-circle': UserCircleIcon,
	'user-group': UserGroupIcon,
	'x-circle': XCircleIcon,
	x: XMarkIcon,
};

const solidIcons = {
	menu: SolidBars3Icon,
	bell: SolidBellIcon,
	'chat-alt-2': SolidChatBubbleLeftRightIcon,
	'check-circle': SolidCheckCircleIcon,
	clock: SolidClockIcon,
	mail: SolidEnvelopeIcon,
	'exclamation-circle': SolidExclamationCircleIcon,
	folder: SolidFolderIcon,
	filter: SolidFunnelIcon,
	heart: SolidHeartIcon,
	search: SolidMagnifyingGlassIcon,
	'paper-clip': SolidPaperClipIcon,
	pause: SolidPauseIcon,
	'pencil-alt': SolidPencilSquareIcon,
	play: SolidPlayIcon,
	trash: SolidTrashIcon,
	user: SolidUserIcon,
	'user-circle': SolidUserCircleIcon,
	'x-circle': SolidXCircleIcon,
	x: SolidXMarkIcon,
};

export default {
	props: {
		name: { type: String, required: true },
		solid: { type: Boolean, default: false },
	},
	computed: {
		iconComponent() {
			return (this.solid && solidIcons[this.name]) || outlineIcons[this.name];
		},
	},
};
</script>
