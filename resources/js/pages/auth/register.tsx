import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { useTranslation } from '@/lib/i18n';

const accountTypes = [
    {
        value: 'client',
        title: 'Rent a place',
        description: 'Faculty, students and exchange students',
    },
    {
        value: 'owner',
        title: 'List my property',
        description: 'Owners of homes and rooms',
    },
] as const;

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Register')} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <fieldset className="grid gap-2">
                                <legend className="mb-2 text-sm font-medium">
                                    {t('I want to')}
                                </legend>
                                <div className="grid grid-cols-2 gap-3">
                                    {accountTypes.map((type) => (
                                        <label
                                            key={type.value}
                                            className="cursor-pointer rounded-lg border p-3 text-sm transition-colors has-[:checked]:border-primary has-[:checked]:bg-accent has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                        >
                                            <input
                                                type="radio"
                                                name="account_type"
                                                value={type.value}
                                                defaultChecked={
                                                    type.value === 'client'
                                                }
                                                className="sr-only"
                                            />
                                            <span className="block font-medium">
                                                {t(type.title)}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {t(type.description)}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.account_type} />
                            </fieldset>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">
                                        {t('First name')}
                                    </Label>
                                    <Input
                                        id="first_name"
                                        name="first_name"
                                        required
                                        autoFocus
                                        autoComplete="given-name"
                                    />
                                    <InputError message={errors.first_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">
                                        {t('Last name')}
                                    </Label>
                                    <Input
                                        id="last_name"
                                        name="last_name"
                                        required
                                        autoComplete="family-name"
                                    />
                                    <InputError message={errors.last_name} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('Email address')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="birth_date">
                                    {t('Birth date')}
                                </Label>
                                <Input
                                    id="birth_date"
                                    type="date"
                                    required
                                    name="birth_date"
                                    autoComplete="bday"
                                />
                                <InputError message={errors.birth_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t('Password')}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder={t('Password')}
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t('Confirm password')}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t('Confirm password')}
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t('Create account')}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t('Already have an account?')}{' '}
                            <TextLink href={login()} tabIndex={6}>
                                {t('Log in')}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
